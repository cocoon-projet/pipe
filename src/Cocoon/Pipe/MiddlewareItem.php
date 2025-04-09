<?php

declare(strict_types=1);

namespace Cocoon\Pipe;

use Cocoon\Pipe\Attribute\Priority;
use Cocoon\Pipe\Attribute\Route;
use Cocoon\Pipe\Conditional\ConditionalMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

class MiddlewareItem implements MiddlewareInterface
{
    private MiddlewareInterface $middleware;
    private ?Route $routeAttribute = null;
    private int $priority = 0;

    public function __construct(MiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
        $this->processAttributes();
    }

    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->middleware->process($request, $handler);
    }

    public function shouldExecute(ServerRequestInterface $request): bool
    {
        // Vérifier si le middleware est conditionnel
        if ($this->middleware instanceof ConditionalMiddlewareInterface
            && !$this->middleware->shouldExecute($request)
        ) {
            return false;
        }

        // Vérifier si le middleware a un attribut de route
        if ($this->routeAttribute !== null) {
            return $this->routeAttribute->matches(
                $request->getUri()->getPath(),
                $request->getMethod()
            );
        }

        return true;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    private function processAttributes(): void
    {
        $reflection = new ReflectionClass($this->middleware);

        // Traiter l'attribut Route
        $routeAttributes = $reflection->getAttributes(Route::class);
        if (!empty($routeAttributes)) {
            $this->routeAttribute = $routeAttributes[0]->newInstance();
        }

        // Traiter l'attribut Priority
        $priorityAttributes = $reflection->getAttributes(Priority::class);
        if (!empty($priorityAttributes)) {
            $this->priority = $priorityAttributes[0]->newInstance()->getValue();
        }
    }
} 