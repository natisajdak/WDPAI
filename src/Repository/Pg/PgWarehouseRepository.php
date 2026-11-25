<?php

declare(strict_types=1);

namespace App\Repository\Pg;

use App\Database\Database;
use App\Domain\Entity\Warehouse;
use App\Repository\Contract\WarehouseRepositoryInterface;

final class PgWarehouseRepository implements WarehouseRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function findAll(): array
    {
        return array_map(
            static fn (array $row): Warehouse => new Warehouse((int) $row['id'], (string) $row['name'], (string) $row['address']),
            $this->database->fetchAll('SELECT id, name, address FROM warehouses ORDER BY name'),
        );
    }
}
