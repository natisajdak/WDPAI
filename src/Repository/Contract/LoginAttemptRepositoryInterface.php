<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use DateTimeImmutable;

interface LoginAttemptRepositoryInterface
{
    public function record(string $email, string $ipAddress, bool $succeeded, DateTimeImmutable $at): void;

    public function countFailuresSince(string $email, string $ipAddress, DateTimeImmutable $since): int;
}
