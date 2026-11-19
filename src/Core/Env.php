<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Odczyt zmiennych środowiskowych z kontrolą typów.
 * Używany wyłącznie w config/app.php: reszta aplikacji dostaje gotowe wartości przez Config.
 */
final class Env
{
    public static function string(string $name, ?string $default = null): string
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            if ($default === null) {
                throw new ConfigurationException(sprintf('Brak wymaganej zmiennej środowiskowej %s.', $name));
            }

            return $default;
        }

        return $value;
    }

    public static function bool(string $name, bool $default = false): bool
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new ConfigurationException(sprintf('Zmienna %s musi mieć wartość logiczną (1/0, true/false).', $name));
        }

        return $parsed;
    }

    public static function int(string $name, int $default): int
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        if ($parsed === false) {
            throw new ConfigurationException(sprintf('Zmienna %s musi być liczbą całkowitą.', $name));
        }

        return $parsed;
    }
}
