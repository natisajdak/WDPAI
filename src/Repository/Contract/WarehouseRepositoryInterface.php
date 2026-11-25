<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Entity\Warehouse;

interface WarehouseRepositoryInterface
{
    /**
     * @return list<Warehouse> posortowane po nazwie
     */
    public function findAll(): array;
}
