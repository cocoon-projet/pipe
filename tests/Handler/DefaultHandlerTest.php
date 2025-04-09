<?php

declare(strict_types=1);

namespace Tests\Handler;

use Cocoon\Pipe\Handler\DefaultHandler;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class DefaultHandlerTest extends TestCase
{
    private DefaultHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new DefaultHandler();
    }

    public function testHandleReturnsDefaultResponse(): void
    {
        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', (string) $response->getBody());
    }

    public function testHandleWithCustomResponse(): void
    {
        $customResponse = new Response();
        $handler = new DefaultHandler($customResponse);
        $request = new ServerRequest();
        
        $response = $handler->handle($request);

        $this->assertSame($customResponse, $response);
    }

    public function testHandleWithModifiedResponse(): void
    {
        $customResponse = (new Response())
            ->withStatus(404)
            ->withHeader('X-Custom', 'test');

        $handler = new DefaultHandler($customResponse);
        $request = new ServerRequest();
        
        $response = $handler->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['test'], $response->getHeader('X-Custom'));
    }
} 