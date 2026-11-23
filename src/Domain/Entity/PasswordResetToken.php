<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

final class PasswordResetToken
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly DateTimeImmutable $expiresAt,
        public readonly ?DateTimeImmutable $usedAt,
    ) {
    }

    public function isUsable(DateTimeImmutable $now): bool
    {
        return $this->usedAt === null && $this->expiresAt > $now;
    }
}
