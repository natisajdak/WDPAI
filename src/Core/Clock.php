<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

/**
 * Źródło bieżącego czasu. Serwisy nie wołają new DateTimeImmutable() bezpośrednio,
 * dzięki czemu testy mogą „zamrozić” czas (np. wygaśnięcie tokenu po 60 minutach).
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
