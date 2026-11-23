<?php

declare(strict_types=1);

namespace App\Repository\Pg;

use App\Database\Database;
use App\Domain\Entity\PasswordResetToken;
use App\Repository\Contract\PasswordResetTokenRepositoryInterface;
use DateTimeImmutable;

final class PgPasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        $this->database->execute(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :hash, :expires_at)',
            ['user_id' => $userId, 'hash' => $tokenHash, 'expires_at' => $expiresAt],
        );
    }

    public function findByHash(string $tokenHash): ?PasswordResetToken
    {
        $row = $this->database->fetchOne(
            'SELECT id, user_id, expires_at, used_at FROM password_reset_tokens WHERE token_hash = :hash',
            ['hash' => $tokenHash],
        );

        if ($row === null) {
            return null;
        }

        return new PasswordResetToken(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            expiresAt: new DateTimeImmutable((string) $row['expires_at']),
            usedAt: $row['used_at'] === null ? null : new DateTimeImmutable((string) $row['used_at']),
        );
    }

    public function markUsed(int $tokenId, DateTimeImmutable $at): void
    {
        $this->database->execute('UPDATE password_reset_tokens SET used_at = :at WHERE id = :id', ['at' => $at, 'id' => $tokenId]);
    }

    public function invalidateForUser(int $userId, DateTimeImmutable $at): void
    {
        $this->database->execute(
            'UPDATE password_reset_tokens SET used_at = :at WHERE user_id = :user_id AND used_at IS NULL',
            ['at' => $at, 'user_id' => $userId],
        );
    }
}
