<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Polityka CSP: skrypty i style tylko z naszej domeny.
 * Jedyny skrypt inline (ustawienie motywu w <head>) dostaje jednorazowy nonce.
 */
final class ContentSecurityPolicy
{
    private ?string $nonce = null;

    public function nonce(): string
    {
        return $this->nonce ??= base64_encode(random_bytes(16));
    }

    public function header(): string
    {
        return implode('; ', [
            "default-src 'self'",
            sprintf("script-src 'self' 'nonce-%s'", $this->nonce()),
            "style-src 'self'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
