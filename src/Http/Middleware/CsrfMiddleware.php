<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Exception\ForbiddenException;
use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use App\Security\Csrf;

/**
 * Każde żądanie zmieniające stan (POST itd.) musi mieć poprawny token CSRF, inaczej kończy się 403.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Csrf $csrf)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if (!$request->isMethodSafe()) {
            $token = $request->header(Csrf::HEADER_NAME) ?? $request->input(Csrf::FIELD_NAME);

            if (!$this->csrf->isValid($token)) {
                throw new ForbiddenException('error.csrf');
            }
        }

        return $next->handle($request);
    }
}
