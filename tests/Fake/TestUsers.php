<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\User;
use App\Domain\Enum\Role;
use DateTimeImmutable;

/**
 * Gotowi użytkownicy do testów. Hash hasła liczony raz, bo bcrypt jest celowo wolny.
 */
final class TestUsers
{
    public const PASSWORD = 'Cargo2026!';

    private static ?string $hash = null;

    public static function make(
        int $id = 1,
        Role $role = Role::Operator,
        string $email = 'operator@cargo.local',
        bool $isActive = true,
        bool $hasPassword = true,
        string $locale = 'pl',
    ): User {
        self::$hash ??= password_hash(self::PASSWORD, PASSWORD_DEFAULT);

        return new User(
            id: $id,
            role: $role,
            email: $email,
            passwordHash: $hasPassword ? self::$hash : null,
            firstName: 'Marek',
            lastName: 'Kowalczyk',
            locale: $locale,
            isActive: $isActive,
            createdAt: new DateTimeImmutable('2026-01-01 08:00:00'),
            lastLoginAt: null,
        );
    }
}
