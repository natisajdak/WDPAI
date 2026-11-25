<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Wartości odpowiadają forklifts.status. „Zarezerwowany” nie jest statusem, tylko wynika z rezerwacji.
 */
enum ForkliftStatus: string
{
    case Available = 'AVAILABLE';
    case OutOfService = 'OUT_OF_SERVICE';
    case Retired = 'RETIRED';

    public function labelKey(): string
    {
        return 'forklift.status.' . strtolower($this->value);
    }
}
