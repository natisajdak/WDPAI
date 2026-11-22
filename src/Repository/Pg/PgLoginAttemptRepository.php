<?php

declare(strict_types=1);

namespace App\Repository\Pg;

use App\Database\Database;
use App\Repository\Contract\LoginAttemptRepositoryInterface;
use DateTimeImmutable;

final class PgLoginAttemptRepository implements LoginAttemptRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function record(string $email, string $ipAddress, bool $succeeded, DateTimeImmutable $at): void
    {
        $this->database->execute(
            'INSERT INTO login_attempts (email, ip_address, succeeded, attempted_at) VALUES (:email, CAST(:ip AS inet), :succeeded, :at)',
            ['email' => $email, 'ip' => $ipAddress, 'succeeded' => $succeeded, 'at' => $at],
        );
    }

    public function countFailuresSince(string $email, string $ipAddress, DateTimeImmutable $since): int
    {
        return (int) $this->database->fetchValue(
            <<<'SQL'
                SELECT count(*)
                  FROM login_attempts
                 WHERE email = :email
                   AND ip_address = CAST(:ip AS inet)
                   AND succeeded = false
                   AND attempted_at >= :since
                SQL,
            ['email' => $email, 'ip' => $ipAddress, 'since' => $since],
        );
    }
}
