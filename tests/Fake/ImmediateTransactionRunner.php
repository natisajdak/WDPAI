<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Database\TransactionRunner;

final class ImmediateTransactionRunner implements TransactionRunner
{
    public int $transactions = 0;

    public function transactional(callable $operation): mixed
    {
        $this->transactions++;

        return $operation();
    }
}
