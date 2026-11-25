<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\TechnicalInspection;
use App\Domain\Entity\Warehouse;
use App\Domain\Query\ForkliftFilter;
use App\Domain\Query\Page;
use App\Domain\ReadModel\FleetStats;
use App\Domain\ReadModel\ForkliftOverview;
use App\Repository\Contract\ForkliftRepositoryInterface;
use App\Repository\Contract\TechnicalInspectionRepositoryInterface;
use App\Repository\Contract\WarehouseRepositoryInterface;

/**
 * Odczyty floty dla listy, szczegółów wózka i pulpitu.
 */
final class ForkliftService
{
    /** Ile dni przed końcem ważności badania UDT pokazujemy ostrzeżenie. */
    public const UDT_WARNING_DAYS = 30;

    public function __construct(
        private readonly ForkliftRepositoryInterface $forklifts,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly TechnicalInspectionRepositoryInterface $inspections,
    ) {
    }

    /**
     * @return Page<ForkliftOverview>
     */
    public function search(ForkliftFilter $filter): Page
    {
        return $this->forklifts->search($filter);
    }

    public function find(int $id): ?ForkliftOverview
    {
        return $this->forklifts->findOverview($id);
    }

    /**
     * @return list<TechnicalInspection>
     */
    public function inspectionHistory(int $forkliftId): array
    {
        return $this->inspections->findByForklift($forkliftId);
    }

    /**
     * @return list<Warehouse>
     */
    public function warehouses(): array
    {
        return $this->warehouses->findAll();
    }

    /**
     * @return list<ForkliftOverview>
     */
    public function bookableNow(int $limit = 6): array
    {
        return $this->forklifts->findBookableNow($limit);
    }

    /**
     * @return list<ForkliftOverview>
     */
    public function udtWarnings(): array
    {
        return $this->forklifts->findUdtExpiringWithin(self::UDT_WARNING_DAYS);
    }

    /**
     * @return list<ForkliftOverview>
     */
    public function outOfService(): array
    {
        return $this->forklifts->findOutOfService();
    }

    public function stats(): FleetStats
    {
        return $this->forklifts->fleetStats();
    }
}
