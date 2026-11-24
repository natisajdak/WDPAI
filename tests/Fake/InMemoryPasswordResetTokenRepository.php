<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\PasswordResetToken;
use App\Repository\Contract\PasswordResetTokenRepositoryInterface;
use DateTimeImmutable;

final class InMemoryPasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    /** @var array<string, PasswordResetToken> klucz: hash tokenu */
    public array $tokens = [];

    private int $nextId = 1;

    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        $this->tokens[$tokenHash] = new PasswordResetToken($this->nextId++, $userId, $expiresAt, null);
    }

    public function findByHash(string $tokenHash): ?PasswordResetToken
    {
        return $this->tokens[$tokenHash] ?? null;
    }

    public function markUsed(int $tokenId, DateTimeImmutable $at): void
    {
        foreach ($this->tokens as $hash => $token) {
            if ($token->id === $tokenId) {
                $this->tokens[$hash] = new PasswordResetToken($token->id, $token->userId, $token->expiresAt, $at);
            }
        }
    }

    public function invalidateForUser(int $userId, DateTimeImmutable $at): void
    {
        foreach ($this->tokens as $hash => $token) {
            if ($token->userId === $userId && $token->usedAt === null) {
                $this->tokens[$hash] = new PasswordResetToken($token->id, $token->userId, $token->expiresAt, $at);
            }
        }
    }
}
