<?php

declare(strict_types=1);

namespace Cocoon\Pipe\Attribute;

use Attribute;

#[Attribute]
class Route
{
    private string $pattern;
    private array $methods;
    private ?string $compiledPattern = null;

    /**
     * @param string $pattern Motif de route (supporte les expressions régulières)
     * @param string[] $methods Méthodes HTTP autorisées
     */
    public function __construct(string $pattern, ?array $methods = ['GET'])
    {
        $this->pattern = $pattern;
        $this->methods = array_map('strtoupper', $methods ?? ['GET']);
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function matches(string $path, string $method): bool
    {
        return $this->matchesPath($path) && $this->matchesMethod($method);
    }

    public function matchesPath(string $path): bool
    {
        if ($this->compiledPattern === null) {
            $this->compiledPattern = $this->compilePattern($this->pattern);
        }

        // Normaliser le chemin à tester
        $path = '/' . ltrim($path, '/');
        return (bool) preg_match($this->compiledPattern, $path);
    }

    public function matchesMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods, true);
    }

    private function compilePattern(string $pattern): string
    {
        // Si le pattern est déjà une regex valide, on le retourne tel quel
        if ($this->isRegexPattern($pattern)) {
            return $pattern;
        }

        // Normaliser le chemin
        $pattern = '/' . ltrim($pattern, '/');

        // Échapper les caractères spéciaux de regex
        $pattern = preg_quote($pattern, '#');

        // Remplacer les wildcards par leurs équivalents regex
        // Important: traiter d'abord ** avant * pour éviter les conflits
        $pattern = str_replace(
            [preg_quote('**', '#'), preg_quote('*', '#')],
            ['.*?', '[^/]*?'],
            $pattern
        );

        return "#^{$pattern}$#";
    }

    private function isRegexPattern(string $pattern): bool
    {
        if (!str_starts_with($pattern, '/')) {
            return false;
        }

        // Vérifier si le pattern est une regex valide
        try {
            if (@preg_match($pattern, '') === false) {
                return false;
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
} 