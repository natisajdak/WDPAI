<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Entity\TechnicalInspection;

interface TechnicalInspectionRepositoryInterface
{
    /**
     * @return list<TechnicalInspection> od najnowszego
     */
    public function findByForklift(int $forkliftId): array;
}
