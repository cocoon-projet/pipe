<?php

declare(strict_types=1);

namespace Tests\Attribute;

use Cocoon\Pipe\Attribute\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testRouteCreation(): void
    {
        $route = new Route('test/*');
        $this->assertEquals('test/*', $route->getPattern());
        $this->assertEquals(['GET'], $route->getMethods());
    }

    public function testRouteWithCustomMethods(): void
    {
        $route = new Route('test/*', methods: ['POST', 'PUT']);
        $this->assertEquals(['POST', 'PUT'], $route->getMethods());
    }

    public function testMatchesPath(): void
    {
        $cases = [
            // Simple wildcard
            ['pattern' => 'api/*', 'path' => '/api/users', 'expected' => true],
            ['pattern' => 'api/*', 'path' => '/other/path', 'expected' => false],
            
            // Double wildcard
            ['pattern' => 'public/**', 'path' => '/public/css/style.css', 'expected' => true],
            ['pattern' => 'public/**', 'path' => '/api/public/file', 'expected' => false],
            
            // Regex pattern
            ['pattern' => '/^\/admin\/.*$/', 'path' => '/admin/dashboard', 'expected' => true],
            ['pattern' => '/^\/admin\/.*$/', 'path' => '/user/profile', 'expected' => false],
            
            // Exact match
            ['pattern' => '/login', 'path' => '/login', 'expected' => true],
            ['pattern' => '/login', 'path' => '/login/settings', 'expected' => false],
            
            // Complex patterns
            ['pattern' => 'api/v1/*', 'path' => '/api/v1/users', 'expected' => true],
            ['pattern' => 'api/v1/*', 'path' => '/api/v2/users', 'expected' => false],
        ];

        foreach ($cases as $case) {
            $route = new Route($case['pattern']);
            $this->assertEquals(
                $case['expected'],
                $route->matchesPath($case['path']),
                "Pattern '{$case['pattern']}' should " . 
                ($case['expected'] ? '' : 'not ') . 
                "match path '{$case['path']}'"
            );
        }
    }

    public function testMatchesMethod(): void
    {
        $cases = [
            // Default methods (GET only)
            ['methods' => null, 'method' => 'GET', 'expected' => true],
            ['methods' => null, 'method' => 'POST', 'expected' => false],
            
            // Custom methods
            ['methods' => ['POST', 'PUT'], 'method' => 'POST', 'expected' => true],
            ['methods' => ['POST', 'PUT'], 'method' => 'GET', 'expected' => false],
            
            // Case insensitive
            ['methods' => ['GET', 'POST'], 'method' => 'get', 'expected' => true],
            ['methods' => ['GET', 'POST'], 'method' => 'post', 'expected' => true],
            
            // Multiple methods
            ['methods' => ['GET', 'POST', 'PUT'], 'method' => 'PUT', 'expected' => true],
            ['methods' => ['GET', 'POST', 'PUT'], 'method' => 'DELETE', 'expected' => false],
        ];

        foreach ($cases as $case) {
            $route = new Route('test/*', methods: $case['methods']);
            $this->assertEquals(
                $case['expected'],
                $route->matchesMethod($case['method']),
                "Methods " . json_encode($case['methods']) . 
                " should " . ($case['expected'] ? '' : 'not ') . 
                "match method '{$case['method']}'"
            );
        }
    }

    public function testMatchesRequest(): void
    {
        $route = new Route('api/users/*', methods: ['GET', 'POST']);

        // Test matching path and method
        $this->assertTrue($route->matches('/api/users/123', 'GET'));
        $this->assertTrue($route->matches('/api/users/123', 'POST'));

        // Test non-matching path
        $this->assertFalse($route->matches('/api/posts/123', 'GET'));

        // Test non-matching method
        $this->assertFalse($route->matches('/api/users/123', 'PUT'));

        // Test both non-matching
        $this->assertFalse($route->matches('/api/posts/123', 'PUT'));
    }

    public function testRegexPatternMatching(): void
    {
        $route = new Route('/^\/api\/users\/\d+$/');

        // Test valid numeric IDs
        $this->assertTrue($route->matchesPath('/api/users/123'));
        $this->assertTrue($route->matchesPath('/api/users/456'));

        // Test invalid paths
        $this->assertFalse($route->matchesPath('/api/users/abc'));
        $this->assertFalse($route->matchesPath('/api/users/'));
        $this->assertFalse($route->matchesPath('/api/posts/123'));
    }

    public function testDoubleWildcardMatching(): void
    {
        $route = new Route('public/**');

        // Test various nested paths
        $this->assertTrue($route->matchesPath('/public/css/style.css'));
        $this->assertTrue($route->matchesPath('/public/js/vendor/jquery.min.js'));
        $this->assertTrue($route->matchesPath('/public/images/logo/main.png'));

        // Test invalid paths
        $this->assertFalse($route->matchesPath('/api/public/file'));
        $this->assertFalse($route->matchesPath('/static/public/file'));
    }
} 