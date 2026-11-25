<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

/**
 * Badanie okresowe UDT. Ważne do końca dnia valid_until włącznie.
 */
final class TechnicalInspection
{
    public function __construct(
        public readonly int $id,
        public readonly int $forkliftId,
        public readonly DateTimeImmutable $inspectionDate,
        public readonly DateTimeImmutable $validUntil,
        public readonly string $protocolNumber,
        public readonly ?string $notes,
    ) {
    }

    public function isValidOn(DateTimeImmutable $day): bool
    {
        return $this->validUntil->format('Y-m-d') >= $day->format('Y-m-d');
    }
}
