<?php


namespace Cocoon\Pipe;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Middlewares dispatcher
 *
 * Class Pipe
 * @package Cocoon\Pipe
 */
class Pipe implements RequestHandlerInterface
{
    /**
     * @var array
     */
    private $middleware = [];
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
         $requestHandler = new Handler($this->get());
         return $requestHandler->handle($request);
    }
    /**
     * Ajoute un ou plusieurs middleware
     *
     * @param string|array $middleware
     */
    public function add($middleware)
    {
        if (empty($middleware)) {
            throw new InvalidArgumentException('$middelware ne doit pas être vide');
        }
        if (is_array($middleware)) {
            foreach ($middleware as $mdl) {
                $this->resolve($mdl);
            }
        } else {
            $this->resolve($middleware);
        }
    }

    /**
     * Liste des middlewares
     *
     * @return array
     */
    private function get()
    {
        return $this->middleware;
    }

    /**
     * Instancie ou pas un middleware
     *
     * @param string|object $middleware
     */
    private function resolve($middleware)
    {
        if (is_string($middleware)) {
            $this->middleware[] = new $middleware;
        } else {
            $this->middleware[] = $middleware;
        }
    }
}
