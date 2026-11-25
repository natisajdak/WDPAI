<?php

declare(strict_types=1);

namespace App\Domain\ReadModel;

/**
 * Liczby na pulpit kierownika. Wycofane wózki nie są wliczane.
 */
final class FleetStats
{
    public function __construct(
        public readonly int $available,
        public readonly int $outOfService,
        public readonly int $inUseNow,
        public readonly int $openFaults,
        public readonly int $udtExpired,
    ) {
    }
}
