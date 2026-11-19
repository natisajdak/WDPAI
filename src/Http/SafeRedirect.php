<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Ochrona przed open redirect: przekierowujemy wyłącznie na ścieżki w obrębie aplikacji.
 */
final class SafeRedirect
{
    /**
     * Zwraca ścieżkę (z query stringiem), jeśli adres wskazuje na tę aplikację, w przeciwnym razie null.
     */
    public static function localPath(?string $candidate): ?string
    {
        if ($candidate === null || $candidate === '') {
            return null;
        }

        // "//evil.com" i "/\evil.com" przeglądarki traktują jak adresy innej domeny.
        if (!str_starts_with($candidate, '/') || str_starts_with($candidate, '//') || str_contains($candidate, '\\')) {
            return null;
        }

        return $candidate;
    }

    /**
     * Strona, z której przyszło żądanie (nagłówek Referer), o ile należy do tej samej aplikacji.
     */
    public static function back(Request $request, string $fallback = '/'): string
    {
        $referer = $request->header('referer');
        $parts = $referer === null ? false : parse_url($referer);

        if ($parts === false || ($parts['host'] ?? null) === null) {
            return $fallback;
        }

        $host = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        if (strcasecmp($host, (string) $request->header('host')) !== 0) {
            return $fallback;
        }

        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        return self::localPath($path) ?? $fallback;
    }
}
