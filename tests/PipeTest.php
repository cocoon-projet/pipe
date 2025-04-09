<?php

declare(strict_types=1);

namespace Tests;

use Cocoon\Pipe\Pipe;
use Cocoon\Pipe\Attribute\Priority;
use Cocoon\Pipe\Attribute\Route;
use Cocoon\Pipe\Conditional\ConditionalMiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Uri;

class PipeTest extends TestCase
{
    private Pipe $pipe;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->pipe = new Pipe();
        $this->request = new ServerRequest(
            [],
            [],
            'http://localhost/test',
            'GET'
        );
    }

    public function testBasicMiddlewareExecution(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('X-Test', 'executed');
            }
        };

        $this->pipe->add($middleware);
        $response = $this->pipe->handle($this->request);

        $this->assertTrue($response->hasHeader('X-Test'));
        $this->assertEquals(['executed'], $response->getHeader('X-Test'));
    }

    public function testPriorityMiddlewareExecution(): void
    {
        $executionOrder = new ExecutionOrderTracker();

        $lowPriority = new class($executionOrder) implements MiddlewareInterface {
            private ExecutionOrderTracker $tracker;

            public function __construct(ExecutionOrderTracker $tracker)
            {
                $this->tracker = $tracker;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->tracker->add('low');
                return $handler->handle($request);
            }
        };

        $highPriority = new #[Priority(100)] class($executionOrder) implements MiddlewareInterface {
            private ExecutionOrderTracker $tracker;

            public function __construct(ExecutionOrderTracker $tracker)
            {
                $this->tracker = $tracker;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->tracker->add('high');
                return $handler->handle($request);
            }
        };

        $this->pipe->add($lowPriority);
        $this->pipe->add($highPriority);
        $this->pipe->handle($this->request);

        $this->assertEquals(['high', 'low'], $executionOrder->getOrder());
    }

    public function testRouteMiddlewareExecution(): void
    {
        $executionTracker = new ExecutionTracker();

        $routeMiddleware = new #[Route('test/*')] class($executionTracker) implements MiddlewareInterface {
            private ExecutionTracker $tracker;

            public function __construct(ExecutionTracker $tracker)
            {
                $this->tracker = $tracker;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->tracker->markExecuted();
                return $handler->handle($request);
            }
        };

        $this->pipe->add($routeMiddleware);
        
        // Test matching route
        $request = $this->request->withUri(new Uri('http://localhost/test/users'));
        $this->pipe->handle($request);
        $this->assertTrue($executionTracker->wasExecuted());

        // Test non-matching route
        $executionTracker->reset();
        $request = $this->request->withUri(new Uri('http://localhost/other/path'));
        $this->pipe->handle($request);
        $this->assertFalse($executionTracker->wasExecuted());
    }

    public function testConditionalMiddlewareExecution(): void
    {
        $executionTracker = new ExecutionTracker();

        $conditionalMiddleware = new class($executionTracker) implements MiddlewareInterface, ConditionalMiddlewareInterface {
            private ExecutionTracker $tracker;

            public function __construct(ExecutionTracker $tracker)
            {
                $this->tracker = $tracker;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->tracker->markExecuted();
                return $handler->handle($request);
            }

            public function shouldExecute(ServerRequestInterface $request): bool
            {
                return $request->hasHeader('X-Execute');
            }
        };

        $this->pipe->add($conditionalMiddleware);

        // Test with condition met
        $request = $this->request->withHeader('X-Execute', 'true');
        $this->pipe->handle($request);
        $this->assertTrue($executionTracker->wasExecuted());

        // Test with condition not met
        $executionTracker->reset();
        $this->pipe->handle($this->request);
        $this->assertFalse($executionTracker->wasExecuted());
    }

    public function testComplexMiddlewareChain(): void
    {
        $executionOrder = new ExecutionOrderTracker();

        // Priority middleware
        $firstMiddleware = new #[Priority(100)] class($executionOrder) implements MiddlewareInterface {
            private ExecutionOrderTracker $tracker;

            public function __construct(ExecutionOrderTracker $tracker)
            {
                $this->tracker = $tracker;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->tracker->add('first');
                return $handler->handle($request);
            }
        };

        // Route middleware
        $secondMiddleware = new #[Route('test/*', methods: ['GET'])] class($executionOrder) implements MiddlewareInterface {
            private ExecutionOrderTracker $tracker;

            public function __construct(ExecutionOrderTracker $tracker)
            {
                $this->tracker = $tracker;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->tracker->add('second');
                return $handler->handle($request);
            }
        };

        // Conditional middleware
        $thirdMiddleware = new class($executionOrder) implements MiddlewareInterface, ConditionalMiddlewareInterface {
            private ExecutionOrderTracker $tracker;

            public function __construct(ExecutionOrderTracker $tracker)
            {
                $this->tracker = $tracker;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->tracker->add('third');
                return $handler->handle($request);
            }

            public function shouldExecute(ServerRequestInterface $request): bool
            {
                return $request->hasHeader('X-Execute');
            }
        };

        $this->pipe->add($thirdMiddleware)
                  ->add($secondMiddleware)
                  ->add($firstMiddleware);

        $request = $this->request
            ->withUri(new Uri('http://localhost/test/users'))
            ->withHeader('X-Execute', 'true');

        $this->pipe->handle($request);

        $this->assertEquals(['first', 'second', 'third'], $executionOrder->getOrder());
    }

    public function testEmptyPipeReturnsDefaultResponse(): void
    {
        $response = $this->pipe->handle($this->request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMiddlewareCanModifyResponse(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response
                    ->withStatus(404)
                    ->withHeader('X-Custom', 'value');
            }
        };

        $this->pipe->add($middleware);
        $response = $this->pipe->handle($this->request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['value'], $response->getHeader('X-Custom'));
    }
}

/**
 * Classe utilitaire pour suivre l'ordre d'exécution des middlewares
 */
class ExecutionOrderTracker
{
    private array $order = [];

    public function add(string $step): void
    {
        $this->order[] = $step;
    }

    public function getOrder(): array
    {
        return $this->order;
    }
}

/**
 * Classe utilitaire pour suivre l'exécution d'un middleware
 */
class ExecutionTracker
{
    private bool $executed = false;

    public function markExecuted(): void
    {
        $this->executed = true;
    }

    public function wasExecuted(): bool
    {
        return $this->executed;
    }

    public function reset(): void
    {
        $this->executed = false;
    }
} 