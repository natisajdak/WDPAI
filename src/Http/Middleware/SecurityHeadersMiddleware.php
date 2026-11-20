<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use App\Security\ContentSecurityPolicy;

final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ContentSecurityPolicy $csp)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $response = $next->handle($request);

        return $response
            ->withHeader('Content-Security-Policy', $this->csp->header())
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
