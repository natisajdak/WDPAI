<?php

declare(strict_types=1);

namespace App\Repository\Pg;

use App\Database\Database;
use App\Domain\Enum\ForkliftStatus;
use App\Domain\Enum\FuelType;
use App\Domain\Query\ForkliftFilter;
use App\Domain\Query\Page;
use App\Domain\ReadModel\FleetStats;
use App\Domain\ReadModel\ForkliftOverview;
use App\Repository\Contract\ForkliftRepositoryInterface;
use DateTimeImmutable;

/**
 * Odczyty floty z widoku v_forklift_overview (wózek + model + magazyn + UDT + usterki w jednym zapytaniu).
 */
final class PgForkliftRepository implements ForkliftRepositoryInterface
{
    private const SELECT = <<<'SQL'
        SELECT f.id, f.internal_code, f.serial_number, f.production_year, f.status, f.warehouse_id, f.warehouse_name,
               f.manufacturer, f.model_name, f.fuel_type, f.max_load_kg, f.max_lift_height_mm,
               f.udt_valid_until, f.is_udt_valid, f.open_faults_count, f.open_critical_faults_count, f.is_in_use_now
          FROM v_forklift_overview f
        SQL;

    public function __construct(private readonly Database $database)
    {
    }

    public function search(ForkliftFilter $filter): Page
    {
        [$where, $params] = $this->whereClause($filter);

        $total = (int) $this->database->fetchValue('SELECT count(*) FROM v_forklift_overview f ' . $where, $params);

        $rows = $this->database->fetchAll(
            self::SELECT . ' ' . $where . ' ORDER BY ' . $this->orderBy($filter->sort) . ' LIMIT :limit OFFSET :offset',
            $params + ['limit' => ForkliftFilter::PER_PAGE, 'offset' => Page::offset($filter->page, ForkliftFilter::PER_PAGE)],
        );

        return new Page(array_map($this->hydrate(...), $rows), $total, $filter->page, ForkliftFilter::PER_PAGE);
    }

    public function findOverview(int $id): ?ForkliftOverview
    {
        $row = $this->database->fetchOne(self::SELECT . ' WHERE f.id = :id', ['id' => $id]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function findBookableNow(int $limit): array
    {
        return $this->fetchList(
            self::SELECT . " WHERE f.status = 'AVAILABLE' AND f.is_udt_valid AND NOT f.is_in_use_now ORDER BY f.internal_code LIMIT :limit",
            ['limit' => $limit],
        );
    }

    public function findUdtExpiringWithin(int $days): array
    {
        return $this->fetchList(
            self::SELECT . " WHERE f.status <> 'RETIRED' AND f.udt_valid_until <= CURRENT_DATE + CAST(:days AS integer) ORDER BY f.udt_valid_until, f.internal_code",
            ['days' => $days],
        );
    }

    public function findOutOfService(): array
    {
        return $this->fetchList(self::SELECT . " WHERE f.status = 'OUT_OF_SERVICE' ORDER BY f.internal_code");
    }

    public function fleetStats(): FleetStats
    {
        $row = $this->database->fetchOne(
            <<<'SQL'
                SELECT count(*) FILTER (WHERE status = 'AVAILABLE') AS available,
                       count(*) FILTER (WHERE status = 'OUT_OF_SERVICE') AS out_of_service,
                       count(*) FILTER (WHERE is_in_use_now) AS in_use_now,
                       coalesce(sum(open_faults_count), 0) AS open_faults,
                       count(*) FILTER (WHERE NOT is_udt_valid) AS udt_expired
                  FROM v_forklift_overview
                 WHERE status <> 'RETIRED'
                SQL,
        ) ?? [];

        return new FleetStats(
            available: (int) ($row['available'] ?? 0),
            outOfService: (int) ($row['out_of_service'] ?? 0),
            inUseNow: (int) ($row['in_use_now'] ?? 0),
            openFaults: (int) ($row['open_faults'] ?? 0),
            udtExpired: (int) ($row['udt_expired'] ?? 0),
        );
    }

    /**
     * Warunki budowane wyłącznie ze stałych fragmentów SQL; wartości z filtra idą jako parametry.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function whereClause(ForkliftFilter $filter): array
    {
        $conditions = [];
        $params = [];

        if ($filter->status === null) {
            $conditions[] = "f.status <> 'RETIRED'";
        } else {
            $conditions[] = 'f.status = :status';
            $params['status'] = $filter->status;
        }

        if ($filter->warehouseId !== null) {
            $conditions[] = 'f.warehouse_id = :warehouse_id';
            $params['warehouse_id'] = $filter->warehouseId;
        }

        if ($filter->fuelType !== null) {
            $conditions[] = 'f.fuel_type = :fuel_type';
            $params['fuel_type'] = $filter->fuelType;
        }

        if ($filter->minLoadKg !== null) {
            $conditions[] = 'f.max_load_kg >= :min_load';
            $params['min_load'] = $filter->minLoadKg;
        }

        if ($filter->searchPattern() !== null) {
            $conditions[] = "concat_ws(' ', f.internal_code, f.serial_number, f.manufacturer, f.model_name) ILIKE :search";
            $params['search'] = $filter->searchPattern();
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    /**
     * Sortowanie z białej listy: tekst od użytkownika nigdy nie trafia do ORDER BY.
     */
    private function orderBy(string $sort): string
    {
        return match ($sort) {
            'load' => 'f.max_load_kg DESC, f.internal_code',
            'udt' => 'f.udt_valid_until NULLS FIRST, f.internal_code',
            default => 'f.internal_code',
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return list<ForkliftOverview>
     */
    private function fetchList(string $sql, array $params = []): array
    {
        return array_map($this->hydrate(...), $this->database->fetchAll($sql, $params));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): ForkliftOverview
    {
        return new ForkliftOverview(
            id: (int) $row['id'],
            internalCode: (string) $row['internal_code'],
            serialNumber: (string) $row['serial_number'],
            productionYear: (int) $row['production_year'],
            status: ForkliftStatus::from((string) $row['status']),
            warehouseId: $row['warehouse_id'] === null ? null : (int) $row['warehouse_id'],
            warehouseName: $row['warehouse_name'] === null ? null : (string) $row['warehouse_name'],
            manufacturer: (string) $row['manufacturer'],
            modelName: (string) $row['model_name'],
            fuelType: FuelType::from((string) $row['fuel_type']),
            maxLoadKg: (int) $row['max_load_kg'],
            maxLiftHeightMm: (int) $row['max_lift_height_mm'],
            udtValidUntil: $row['udt_valid_until'] === null ? null : new DateTimeImmutable((string) $row['udt_valid_until']),
            isUdtValid: (bool) $row['is_udt_valid'],
            openFaultsCount: (int) $row['open_faults_count'],
            openCriticalFaultsCount: (int) $row['open_critical_faults_count'],
            isInUseNow: (bool) $row['is_in_use_now'],
        );
    }
}
