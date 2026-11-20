<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Token CSRF przechowywany w sesji (synchronizer token pattern).
 * Formularze wysyłają go w polu _csrf, a żądania Fetch w nagłówku X-CSRF-Token.
 */
final class Csrf
{
    public const FIELD_NAME = '_csrf';
    public const HEADER_NAME = 'X-CSRF-Token';

    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = $this->regenerate();
        }

        return $token;
    }

    /**
     * Nowy token, np. po zalogowaniu, żeby token sprzed logowania przestał działać.
     */
    public function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set(self::SESSION_KEY, $token);

        return $token;
    }

    public function isValid(mixed $token): bool
    {
        $expected = $this->session->get(self::SESSION_KEY);

        // hash_equals porównuje w stałym czasie, więc czas odpowiedzi nie zdradza poprawnych znaków.
        return is_string($expected) && $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }
}
