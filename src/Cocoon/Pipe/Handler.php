<?php
declare(strict_types=1);

namespace Cocoon\Pipe;

use Iterator;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class Handler implements Iterator, RequestHandlerInterface
{
    /** @var array<int, MiddlewareItem> */
    private array $middleware;
    
    private int $position;

    /**
     * @param array<int, MiddlewareItem> $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        $this->middleware = $middlewares;
        $this->rewind();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middlewareItem = $this->get();
        $this->next();

        if ($middlewareItem === null) {
            return new Response();
        }

        $middleware = $middlewareItem->getMiddleware();
        return $middleware->process($request, $this);
    }

    private function get(): ?MiddlewareItem
    {
        return $this->valid() ? $this->current() : null;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function current(): MiddlewareItem
    {
        return $this->middleware[$this->key()];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->middleware[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
