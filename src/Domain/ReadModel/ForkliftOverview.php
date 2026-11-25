<?php

declare(strict_types=1);

namespace App\Domain\ReadModel;

use App\Domain\Enum\ForkliftStatus;
use App\Domain\Enum\FuelType;
use DateTimeImmutable;

/**
 * Wózek razem z modelem, magazynem, ważnością UDT i liczbą usterek (widok v_forklift_overview).
 * Model do odczytu: listy, szczegóły i pulpit. Zmiany danych wózka będą szły przez osobną encję.
 */
final class ForkliftOverview
{
    public function __construct(
        public readonly int $id,
        public readonly string $internalCode,
        public readonly string $serialNumber,
        public readonly int $productionYear,
        public readonly ForkliftStatus $status,
        public readonly ?int $warehouseId,
        public readonly ?string $warehouseName,
        public readonly string $manufacturer,
        public readonly string $modelName,
        public readonly FuelType $fuelType,
        public readonly int $maxLoadKg,
        public readonly int $maxLiftHeightMm,
        public readonly ?DateTimeImmutable $udtValidUntil,
        public readonly bool $isUdtValid,
        public readonly int $openFaultsCount,
        public readonly int $openCriticalFaultsCount,
        public readonly bool $isInUseNow,
    ) {
    }

    public function displayName(): string
    {
        return sprintf('%s · %s %s', $this->internalCode, $this->manufacturer, $this->modelName);
    }

    /**
     * Czy wózek w ogóle można rezerwować (R1, R2). Kolizje terminów sprawdza rezerwacja.
     */
    public function isBookable(): bool
    {
        return $this->status === ForkliftStatus::Available && $this->isUdtValid;
    }

    public function udtExpiresWithinDays(int $days, DateTimeImmutable $today): bool
    {
        return $this->udtValidUntil !== null
            && $this->udtValidUntil->format('Y-m-d') <= $today->modify(sprintf('+%d days', $days))->format('Y-m-d');
    }
}
