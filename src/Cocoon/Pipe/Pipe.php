<?php

declare(strict_types=1);

namespace Cocoon\Pipe;

use Cocoon\Pipe\Handler\DefaultHandler;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Middlewares dispatcher
 *
 * Class Pipe
 * @package Cocoon\Pipe
 */
final class Pipe implements RequestHandlerInterface
{
    /** @var MiddlewareItem[] */
    private array $middlewares = [];
    private RequestHandlerInterface $fallbackHandler;

    public function __construct(?RequestHandlerInterface $fallbackHandler = null)
    {
        $this->fallbackHandler = $fallbackHandler ?? new DefaultHandler();
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->createHandler();
        return $handler->handle($request);
    }

    /**
     * Ajoute un ou plusieurs middleware
     *
     * @param MiddlewareInterface|string $middleware
     * @param int|null $priority Priorité du middleware (plus le nombre est élevé, plus la priorité est haute)
     * @throws InvalidArgumentException Si le middleware est vide ou invalide
     */
    public function add(MiddlewareInterface|string $middleware): self
    {
        $this->resolve($middleware);
        $this->sortMiddlewares();
        return $this;
    }

    /**
     * Liste des middlewares
     *
     * @return array
     */
    private function get()
    {
        return $this->middlewares;
    }

    /**
     * Instancie ou pas un middleware
     *
     * @param MiddlewareInterface|string $middleware
     * @param int|null $priority
     * @throws InvalidArgumentException Si la classe du middleware n'implémente pas MiddlewareInterface
     */
    private function resolve(MiddlewareInterface|string $middleware, ?int $priority = null): void
    {
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new InvalidArgumentException(
                    sprintf('La classe middleware "%s" n\'existe pas', $middleware)
                );
            }

            $instance = new $middleware();
            
            if (!$instance instanceof MiddlewareInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'La classe middleware "%s" doit implémenter l\'interface %s',
                        $middleware,
                        MiddlewareInterface::class
                    )
                );
            }

            $this->middlewares[] = new MiddlewareItem($instance);
        } else {
            $this->middlewares[] = new MiddlewareItem($middleware);
        }
    }

    /**
     * Trie les middlewares par priorité (du plus prioritaire au moins prioritaire)
     */
    private function sortMiddlewares(): void
    {
        usort($this->middlewares, static function (MiddlewareItem $a, MiddlewareItem $b): int {
            return $a->getPriority() <=> $b->getPriority();
        });
    }

    private function createHandler(): RequestHandlerInterface
    {
        $handler = $this->fallbackHandler;
        foreach ($this->middlewares as $middleware) {
            $handler = new class($middleware, $handler) implements RequestHandlerInterface {
                private MiddlewareItem $middleware;
                private RequestHandlerInterface $handler;

                public function __construct(MiddlewareItem $middleware, RequestHandlerInterface $handler)
                {
                    $this->middleware = $middleware;
                    $this->handler = $handler;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    if ($this->middleware->shouldExecute($request)) {
                        return $this->middleware->process($request, $this->handler);
                    }
                    return $this->handler->handle($request);
                }
            };
        }
        return $handler;
    }
}
