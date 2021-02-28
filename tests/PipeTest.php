<?php
namespace Pipe;

use Cocoon\Pipe\Pipe;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PipeTest extends TestCase
{
    public function testAddStringMiddleware()
    {
        $pipe = new Pipe();
        $pipe->add(OneMiddleware::class);
        $result = $pipe->handle(ServerRequestFactory::fromGlobals());
        $this->assertSame('add middleware', (string) $result->getBody());
    }

    public function testAddInstanceMiddleware()
    {
        $pipe = new Pipe();
        $pipe->add(new OneMiddleware());
        $result = $pipe->handle(ServerRequestFactory::fromGlobals());
        $this->assertSame('add middleware', (string) $result->getBody());
    }

    public function testAddByArrayMiddleware()
    {
        $pipe = new Pipe();
        $pipe->add([new OneMiddleware()]);
        $result = $pipe->handle(ServerRequestFactory::fromGlobals());
        $this->assertSame('add middleware', (string) $result->getBody());
    }
    public function testAddEmptyMiddleware()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$middelware ne doit pas être vide');
        
        $pipe = new Pipe();
        $pipe->add([]);
        $pipe->handle(ServerRequestFactory::fromGlobals());
    }

    public function testBadMiddleware()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('le middleware doit implementer l\'interface MiddelwareInterface');

        $pipe = new Pipe();
        $pipe->add(BadMiddleware::class);
        $pipe->handle(ServerRequestFactory::fromGlobals());
    }

    public function testQueueMiddleware()
    {
        $pipe = new Pipe();
        $pipe->add([TwoMiddleware::class, new OneMiddleware()]);
        $result = $pipe->handle(ServerRequestFactory::fromGlobals());
        $this->assertSame('add middleware hello', (string) $result->getBody());
    }

}
