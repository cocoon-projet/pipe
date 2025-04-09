<?php

declare(strict_types=1);

namespace Cocoon\Pipe\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Priority
{
    public function __construct(
        private int $value = 0
    ) {
    }

    public function getValue(): int
    {
        return $this->value;
    }
} 