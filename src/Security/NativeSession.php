<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Sesja PHP z bezpiecznymi ustawieniami cookie i wygasaniem po okresie bezczynności.
 * Pozostałe flagi (use_strict_mode, use_only_cookies) ustawia docker/php/php.ini.
 */
final class NativeSession implements SessionInterface
{
    private const LAST_ACTIVITY_KEY = '_last_activity';
    private const FLASH_KEY = '_flash';

    public function __construct(
        private readonly string $name,
        private readonly bool $secure,
        private readonly int $idleTimeoutSeconds,
    ) {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.gc_maxlifetime', (string) $this->idleTimeoutSeconds);
        session_start();

        $this->expireIfIdle();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];

        $params = session_get_cookie_params();
        setcookie($this->name, '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);

        session_destroy();
    }

    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        $value = $_SESSION[self::FLASH_KEY][$key] ?? $default;
        unset($_SESSION[self::FLASH_KEY][$key]);

        return $value;
    }

    private function expireIfIdle(): void
    {
        $lastActivity = $_SESSION[self::LAST_ACTIVITY_KEY] ?? null;

        if (is_int($lastActivity) && time() - $lastActivity > $this->idleTimeoutSeconds) {
            $_SESSION = [];
            session_regenerate_id(true);
        }

        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
    }
}
