<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Entity\User;
use DateTimeImmutable;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * @param string $email adres już znormalizowany do małych liter
     */
    public function findByEmail(string $email): ?User;

    /**
     * @return list<User> posortowani po nazwisku i imieniu
     */
    public function findAll(): array;

    public function updateLastLogin(int $userId, DateTimeImmutable $at): void;

    public function updatePasswordHash(int $userId, string $passwordHash): void;

    public function updateLocale(int $userId, string $locale): void;
}
