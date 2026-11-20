<?php

declare(strict_types=1);

namespace App\Security;

interface SessionInterface
{
    public function start(): void;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    /**
     * Nowy identyfikator sesji z zachowaniem danych. Wywoływane po zalogowaniu (ochrona przed session fixation).
     */
    public function regenerate(): void;

    /**
     * Usuwa dane sesji i wygasza cookie (wylogowanie).
     */
    public function destroy(): void;

    /**
     * Wartość dostępna tylko przy następnym odczycie, np. komunikat po przekierowaniu.
     */
    public function flash(string $key, mixed $value): void;

    public function pullFlash(string $key, mixed $default = null): mixed;
}
