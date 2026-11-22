<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Hashowanie haseł algorytmem domyślnym PHP (bcrypt). Hasło nigdy nie jest porównywane jako tekst.
 */
final class PasswordHasher
{
    /**
     * Hash losowego tekstu. Sprawdzamy go, gdy konto nie istnieje albo nie ma hasła,
     * żeby czas odpowiedzi nie zdradzał, które adresy e-mail są w bazie.
     */
    private const DUMMY_HASH = '$2y$12$QY9gtqY2J97e3WnlBa7A9uE.6x/S4iWgHh5uYPMWfSVAc/bz/2vOO';

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verify(string $password, ?string $hash): bool
    {
        if ($hash === null) {
            password_verify($password, self::DUMMY_HASH);

            return false;
        }

        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
