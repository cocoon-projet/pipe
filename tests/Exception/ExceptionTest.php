<?php

declare(strict_types=1);

namespace Tests\Exception;

use Cocoon\Pipe\Exception\InvalidMiddlewareException;
use Cocoon\Pipe\Exception\MiddlewareNotFoundException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testInvalidMiddlewareException(): void
    {
        $exception = new InvalidMiddlewareException('Test middleware is invalid');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test middleware is invalid', $exception->getMessage());
    }

    public function testMiddlewareNotFoundException(): void
    {
        $exception = new MiddlewareNotFoundException('Middleware not found');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Middleware not found', $exception->getMessage());
    }

    public function testInvalidMiddlewareExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous error');
        $exception = new InvalidMiddlewareException(
            'Test middleware is invalid',
            0,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testMiddlewareNotFoundExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous error');
        $exception = new MiddlewareNotFoundException(
            'Middleware not found',
            0,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidMiddlewareExceptionWithCode(): void
    {
        $code = 100;
        $exception = new InvalidMiddlewareException(
            'Test middleware is invalid',
            $code
        );

        $this->assertEquals($code, $exception->getCode());
    }

    public function testMiddlewareNotFoundExceptionWithCode(): void
    {
        $code = 404;
        $exception = new MiddlewareNotFoundException(
            'Middleware not found',
            $code
        );

        $this->assertEquals($code, $exception->getCode());
    }
} 