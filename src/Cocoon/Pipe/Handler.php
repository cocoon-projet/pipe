<?php
namespace Cocoon\Pipe;

use Iterator;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Handler implements iterator, RequestHandlerInterface
{
    /**
     * @var array
     */
    private $middleware = [];
    /**
     * @var int
     */
    private $position;

    /**
     * Handler constructor.
     * @param array $middlewares
     */
    public function __construct($middlewares = [])
    {
        $this->rewind();
        $this->middleware = $middlewares;
    }
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->get();
        $this->next();

        if (null === $middleware) {
            return new Response();
        }
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        } else {
            throw new RuntimeException('le middleware doit implementer l\'interface MiddelwareInterface');
        }
    }

    /**
     * Retourne le middleware courrant
     * a
     * @return mixed|null
     */
    private function get()
    {
        if ($this->valid()) {
            return $this->current();
        }
        return null;
    }

    /**
     * Pour passer au middleware suivant
     *
     * @return int
     */
    public function next()
    {
        return $this->position++;
    }

    /**
     * The current midlleware
     *
     * @return MiddlewareInterface
     */
    public function current()
    {
        return $this->middleware[$this->key()];
    }

    /**
     * current key of middleware
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * a valid middleware
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->middleware[$this->position]);
    }

    /**
     * rewind middleware position
     */
    public function rewind()
    {
        $this->position = 0;
    }
}
