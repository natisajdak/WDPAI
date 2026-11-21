<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Security\SessionInterface;

/**
 * Sesja w pamięci do testów: bez cookie i nagłówków HTTP.
 */
final class ArraySession implements SessionInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, mixed> */
    private array $flash = [];

    public int $regenerations = 0;

    public bool $destroyed = false;

    public function start(): void
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
        $this->regenerations++;
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->flash = [];
        $this->destroyed = true;
    }

    public function flash(string $key, mixed $value): void
    {
        $this->flash[$key] = $value;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->flash[$key] ?? $default;
        unset($this->flash[$key]);

        return $value;
    }
}
