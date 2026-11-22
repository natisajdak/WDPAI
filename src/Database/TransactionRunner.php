<?php

declare(strict_types=1);

namespace App\Database;

/**
 * Granica transakcji dla serwisów. Serwis nie zna PDO, a w testach zastępuje go atrapa.
 */
interface TransactionRunner
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
