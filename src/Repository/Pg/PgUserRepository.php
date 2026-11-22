<?php

declare(strict_types=1);

namespace App\Repository\Pg;

use App\Database\Database;
use App\Domain\Entity\User;
use App\Domain\Enum\Role;
use App\Repository\Contract\UserRepositoryInterface;
use DateTimeImmutable;

final class PgUserRepository implements UserRepositoryInterface
{
    // Rola jest w osobnej tabeli, więc każde pobranie użytkownika łączy users z roles.
    private const SELECT = <<<'SQL'
        SELECT u.id, r.code AS role_code, u.email, u.password_hash, u.first_name, u.last_name,
               u.locale, u.is_active, u.created_at, u.last_login_at
          FROM users u
          JOIN roles r ON r.id = u.role_id
        SQL;

    public function __construct(private readonly Database $database)
    {
    }

    public function findById(int $id): ?User
    {
        $row = $this->database->fetchOne(self::SELECT . ' WHERE u.id = :id', ['id' => $id]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->database->fetchOne(self::SELECT . ' WHERE u.email = :email', ['email' => $email]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function findAll(): array
    {
        $rows = $this->database->fetchAll(self::SELECT . ' ORDER BY u.last_name, u.first_name, u.id');

        return array_map($this->hydrate(...), $rows);
    }

    public function updateLastLogin(int $userId, DateTimeImmutable $at): void
    {
        $this->database->execute('UPDATE users SET last_login_at = :at WHERE id = :id', ['at' => $at, 'id' => $userId]);
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $this->database->execute('UPDATE users SET password_hash = :hash WHERE id = :id', ['hash' => $passwordHash, 'id' => $userId]);
    }

    public function updateLocale(int $userId, string $locale): void
    {
        $this->database->execute('UPDATE users SET locale = :locale WHERE id = :id', ['locale' => $locale, 'id' => $userId]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            role: Role::from((string) $row['role_code']),
            email: (string) $row['email'],
            passwordHash: $row['password_hash'] === null ? null : (string) $row['password_hash'],
            firstName: (string) $row['first_name'],
            lastName: (string) $row['last_name'],
            locale: (string) $row['locale'],
            isActive: (bool) $row['is_active'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            lastLoginAt: $row['last_login_at'] === null ? null : new DateTimeImmutable((string) $row['last_login_at']),
        );
    }
}
