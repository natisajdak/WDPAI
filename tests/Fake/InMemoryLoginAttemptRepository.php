<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Repository\Contract\LoginAttemptRepositoryInterface;
use DateTimeImmutable;

final class InMemoryLoginAttemptRepository implements LoginAttemptRepositoryInterface
{
    /** @var list<array{email: string, ip: string, succeeded: bool, at: DateTimeImmutable}> */
    public array $attempts = [];

    public function record(string $email, string $ipAddress, bool $succeeded, DateTimeImmutable $at): void
    {
        $this->attempts[] = ['email' => $email, 'ip' => $ipAddress, 'succeeded' => $succeeded, 'at' => $at];
    }

    public function countFailuresSince(string $email, string $ipAddress, DateTimeImmutable $since): int
    {
        return count(array_filter(
            $this->attempts,
            static fn (array $a): bool => $a['email'] === $email && $a['ip'] === $ipAddress && !$a['succeeded'] && $a['at'] >= $since,
        ));
    }
}
