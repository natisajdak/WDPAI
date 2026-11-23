<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Entity\PasswordResetToken;
use DateTimeImmutable;

interface PasswordResetTokenRepositoryInterface
{
    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void;

    public function findByHash(string $tokenHash): ?PasswordResetToken;

    public function markUsed(int $tokenId, DateTimeImmutable $at): void;

    /**
     * Unieważnia wszystkie niewykorzystane tokeny użytkownika (stary link przestaje działać po wysłaniu nowego).
     */
    public function invalidateForUser(int $userId, DateTimeImmutable $at): void;
}
