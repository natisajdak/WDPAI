<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum FuelType: string
{
    case Electric = 'ELECTRIC';
    case Lpg = 'LPG';
    case Diesel = 'DIESEL';

    public function labelKey(): string
    {
        return 'fuel.' . strtolower($this->value);
    }
}
