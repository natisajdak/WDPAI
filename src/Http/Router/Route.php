<?php

declare(strict_types=1);

namespace App\Http\Router;

use InvalidArgumentException;

/**
 * Pojedyncza trasa. Wzorzec obsługuje parametry {name} (dowolny segment)
 * oraz {name:regex}, np. {id:\d+}, {locale:pl|en} albo {year:\d{4}}.
 */
final class Route
{
    private readonly string $regex;

    /**
     * @param array{0: class-string, 1: string} $handler kontroler i nazwa metody
     * @param list<string> $middleware
     */
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly array $handler,
        public readonly array $middleware = [],
    ) {
        $this->regex = self::compile($pattern);
    }

    /**
     * @return array<string, string>|null parametry trasy albo null, gdy ścieżka nie pasuje
     */
    public function match(string $path): ?array
    {
        if (preg_match($this->regex, $path, $matches) !== 1) {
            return null;
        }

        $params = [];

        foreach ($matches as $name => $value) {
            if (is_string($name)) {
                $params[$name] = rawurldecode($value);
            }
        }

        return $params;
    }

    private static function compile(string $pattern): string
    {
        $regex = '';
        $literal = '';
        $length = strlen($pattern);
        $position = 0;

        while ($position < $length) {
            if ($pattern[$position] !== '{') {
                $literal .= $pattern[$position++];
                continue;
            }

            $regex .= preg_quote($literal, '#');
            $literal = '';

            $end = self::closingBrace($pattern, $position);
            [$name, $constraint] = array_pad(explode(':', substr($pattern, $position + 1, $end - $position - 1), 2), 2, null);

            if (preg_match('/^[a-z_][a-z0-9_]*$/i', (string) $name) !== 1) {
                throw new InvalidArgumentException(sprintf('Niepoprawna nazwa parametru w trasie %s.', $pattern));
            }

            // Grupa (?:...) sprawia, że alternatywa "pl|en" dotyczy tylko tego parametru.
            $regex .= sprintf('(?P<%s>%s)', $name, $constraint === null ? '[^/]+' : '(?:' . $constraint . ')');
            $position = $end + 1;
        }

        return '#^' . $regex . preg_quote($literal, '#') . '$#';
    }

    /**
     * Szuka nawiasu zamykającego parametr z uwzględnieniem kwantyfikatorów, np. w {year:\d{4}}.
     */
    private static function closingBrace(string $pattern, int $open): int
    {
        $depth = 0;

        for ($position = $open, $length = strlen($pattern); $position < $length; $position++) {
            $depth += match ($pattern[$position]) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };

            if ($depth === 0) {
                return $position;
            }
        }

        throw new InvalidArgumentException(sprintf('Niezamknięty parametr w trasie %s.', $pattern));
    }
}
