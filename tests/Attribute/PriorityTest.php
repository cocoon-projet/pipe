<?php

declare(strict_types=1);

namespace Tests\Attribute;

use Cocoon\Pipe\Attribute\Priority;
use PHPUnit\Framework\TestCase;

class PriorityTest extends TestCase
{
    public function testPriorityCreation(): void
    {
        $priority = new Priority(100);
        $this->assertEquals(100, $priority->getValue());
    }

    public function testDefaultPriority(): void
    {
        $priority = new Priority();
        $this->assertEquals(0, $priority->getValue());
    }

    public function testNegativePriority(): void
    {
        $priority = new Priority(-50);
        $this->assertEquals(-50, $priority->getValue());
    }

    public function testPriorityComparison(): void
    {
        $highPriority = new Priority(100);
        $mediumPriority = new Priority(50);
        $lowPriority = new Priority(0);
        $negativePriority = new Priority(-50);

        // Test getValue() returns correct values
        $this->assertEquals(100, $highPriority->getValue());
        $this->assertEquals(50, $mediumPriority->getValue());
        $this->assertEquals(0, $lowPriority->getValue());
        $this->assertEquals(-50, $negativePriority->getValue());

        // Test relative priorities
        $this->assertGreaterThan($mediumPriority->getValue(), $highPriority->getValue());
        $this->assertGreaterThan($lowPriority->getValue(), $mediumPriority->getValue());
        $this->assertGreaterThan($negativePriority->getValue(), $lowPriority->getValue());
    }
} 