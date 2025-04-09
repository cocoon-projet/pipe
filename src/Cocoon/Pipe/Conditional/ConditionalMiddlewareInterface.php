<?php

declare(strict_types=1);

namespace Cocoon\Pipe\Conditional;

use Psr\Http\Message\ServerRequestInterface;

interface ConditionalMiddlewareInterface
{
    /**
     * Détermine si le middleware doit être exécuté
     */
    public function shouldExecute(ServerRequestInterface $request): bool;
} 