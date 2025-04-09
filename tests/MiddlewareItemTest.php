<?php

declare(strict_types=1);

namespace Tests;

use Cocoon\Pipe\Attribute\Priority;
use Cocoon\Pipe\Attribute\Route;
use Cocoon\Pipe\Conditional\ConditionalMiddlewareInterface;
use Cocoon\Pipe\MiddlewareItem;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareItemTest extends TestCase
{
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
    }

    public function testMiddlewareItemWithoutAttributes(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $item = new MiddlewareItem($middleware);

        $this->assertEquals(0, $item->getPriority());
        $this->assertTrue($item->shouldExecute($this->request));
    }

    public function testMiddlewareItemWithPriority(): void
    {
        $middleware = new #[Priority(100)] class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $item = new MiddlewareItem($middleware);
        $this->assertEquals(100, $item->getPriority());
    }

    public function testMiddlewareItemWithRoute(): void
    {
        $middleware = new #[Route('api/*', methods: ['GET'])] class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $item = new MiddlewareItem($middleware);

        // Test matching route and method
        $request = $this->request
            ->withUri($this->request->getUri()->withPath('/api/users'))
            ->withMethod('GET');
        $this->assertTrue($item->shouldExecute($request));

        // Test non-matching route
        $request = $this->request
            ->withUri($this->request->getUri()->withPath('/other/path'))
            ->withMethod('GET');
        $this->assertFalse($item->shouldExecute($request));

        // Test non-matching method
        $request = $this->request
            ->withUri($this->request->getUri()->withPath('/api/users'))
            ->withMethod('POST');
        $this->assertFalse($item->shouldExecute($request));
    }

    public function testMiddlewareItemWithConditional(): void
    {
        $middleware = new class implements MiddlewareInterface, ConditionalMiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }

            public function shouldExecute(ServerRequestInterface $request): bool
            {
                return $request->hasHeader('X-Execute');
            }
        };

        $item = new MiddlewareItem($middleware);

        // Test condition met
        $request = $this->request->withHeader('X-Execute', 'true');
        $this->assertTrue($item->shouldExecute($request));

        // Test condition not met
        $this->assertFalse($item->shouldExecute($this->request));
    }

    public function testMiddlewareItemWithRouteAndConditional(): void
    {
        $middleware = new #[Route('api/*')] class implements MiddlewareInterface, ConditionalMiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }

            public function shouldExecute(ServerRequestInterface $request): bool
            {
                return $request->hasHeader('X-Execute');
            }
        };

        $item = new MiddlewareItem($middleware);

        // Test both conditions met
        $request = $this->request
            ->withUri($this->request->getUri()->withPath('/api/users'))
            ->withHeader('X-Execute', 'true');
        $this->assertTrue($item->shouldExecute($request));

        // Test route matches but condition fails
        $request = $this->request
            ->withUri($this->request->getUri()->withPath('/api/users'));
        $this->assertFalse($item->shouldExecute($request));

        // Test condition met but route fails
        $request = $this->request
            ->withUri($this->request->getUri()->withPath('/other/path'))
            ->withHeader('X-Execute', 'true');
        $this->assertFalse($item->shouldExecute($request));
    }

    public function testMiddlewareItemProcess(): void
    {
        $executionTracker = new ExecutionTracker();
        $middleware = new class($executionTracker) implements MiddlewareInterface {
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

        $item = new MiddlewareItem($middleware);
        $response = $item->process($this->request, $this->handler);

        $this->assertTrue($executionTracker->wasExecuted());
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testMiddlewareItemWithComplexRoute(): void
    {
        $middleware = new #[Route('/^\/api\/users\/\d+$/')] class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $item = new MiddlewareItem($middleware);

        // Test valid paths
        $request = $this->request->withUri($this->request->getUri()->withPath('/api/users/123'));
        $this->assertTrue($item->shouldExecute($request));

        // Test invalid paths
        $request = $this->request->withUri($this->request->getUri()->withPath('/api/users/abc'));
        $this->assertFalse($item->shouldExecute($request));
    }

    public function testMiddlewareItemWithDoubleWildcard(): void
    {
        $middleware = new #[Route('public/**')] class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $item = new MiddlewareItem($middleware);

        // Test nested paths
        $paths = [
            '/public/css/style.css',
            '/public/js/vendor/jquery.min.js',
            '/public/images/logo/main.png'
        ];

        foreach ($paths as $path) {
            $request = $this->request->withUri($this->request->getUri()->withPath($path));
            $this->assertTrue($item->shouldExecute($request), "Path '$path' should match");
        }

        // Test invalid paths
        $invalidPaths = [
            '/api/public/file',
            '/static/public/file'
        ];

        foreach ($invalidPaths as $path) {
            $request = $this->request->withUri($this->request->getUri()->withPath($path));
            $this->assertFalse($item->shouldExecute($request), "Path '$path' should not match");
        }
    }
} 