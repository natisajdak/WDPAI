<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\Role;
use DateTimeImmutable;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly Role $role,
        public readonly string $email,
        public readonly ?string $passwordHash,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $locale,
        public readonly bool $isActive,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $lastLoginAt,
    ) {
    }

    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Konto bez hasła czeka na ustawienie go z linku w e-mailu powitalnym.
     */
    public function canSignIn(): bool
    {
        return $this->isActive && $this->passwordHash !== null;
    }
}
