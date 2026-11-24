<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\User;
use App\Repository\Contract\UserRepositoryInterface;
use DateTimeImmutable;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, User> */
    private array $users = [];

    /** @var array<int, DateTimeImmutable> */
    public array $lastLogins = [];

    /** @var array<int, string> */
    public array $passwordHashes = [];

    /** @var array<int, string> */
    public array $locales = [];

    public function __construct(User ...$users)
    {
        foreach ($users as $user) {
            $this->users[$user->id] = $user;
        }
    }

    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->users);
    }

    public function updateLastLogin(int $userId, DateTimeImmutable $at): void
    {
        $this->lastLogins[$userId] = $at;
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $this->passwordHashes[$userId] = $passwordHash;
    }

    public function updateLocale(int $userId, string $locale): void
    {
        $this->locales[$userId] = $locale;
    }
}
