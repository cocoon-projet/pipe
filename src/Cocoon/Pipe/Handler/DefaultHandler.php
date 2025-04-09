<?php

declare(strict_types=1);

namespace Cocoon\Pipe\Handler;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DefaultHandler implements RequestHandlerInterface
{
    private ResponseInterface $response;

    public function __construct(?ResponseInterface $response = null)
    {
        $this->response = $response ?? new Response();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
} 