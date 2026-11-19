<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Niezmienna konfiguracja aplikacji odczytywana kluczami z kropkami, np. "db.host".
 */
final class Config
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    public function get(string $key): mixed
    {
        $value = $this->values;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                // Literówka w kluczu ma się ujawnić od razu, a nie jako tajemnicza wartość null.
                throw new ConfigurationException(sprintf('Brak klucza konfiguracji "%s".', $key));
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function string(string $key): string
    {
        return (string) $this->get($key);
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }
}
