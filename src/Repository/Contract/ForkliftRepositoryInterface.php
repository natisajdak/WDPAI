<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Query\ForkliftFilter;
use App\Domain\Query\Page;
use App\Domain\ReadModel\FleetStats;
use App\Domain\ReadModel\ForkliftOverview;

interface ForkliftRepositoryInterface
{
    /**
     * @return Page<ForkliftOverview>
     */
    public function search(ForkliftFilter $filter): Page;

    public function findOverview(int $id): ?ForkliftOverview;

    /**
     * Wózki, które można wziąć od razu: dostępne, z ważnym UDT i bez trwającej rezerwacji.
     *
     * @return list<ForkliftOverview>
     */
    public function findBookableNow(int $limit): array;

    /**
     * Wózki w eksploatacji z badaniem UDT wygasłym albo wygasającym w ciągu podanej liczby dni.
     *
     * @return list<ForkliftOverview>
     */
    public function findUdtExpiringWithin(int $days): array;

    /**
     * @return list<ForkliftOverview>
     */
    public function findOutOfService(): array;

    public function fleetStats(): FleetStats;
}
