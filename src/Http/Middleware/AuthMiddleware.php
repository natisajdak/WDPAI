<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Entity\User;
use App\Http\Exception\UnauthorizedException;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use App\Security\SessionInterface;

/**
 * Trasy tylko dla zalogowanych. Strona HTML przekierowuje do logowania, API odpowiada 401.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public const INTENDED_URL_KEY = 'intended_url';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if (!$request->attribute(CurrentUserMiddleware::ATTRIBUTE) instanceof User) {
            if ($request->wantsJson()) {
                throw new UnauthorizedException();
            }

            if ($request->method() === 'GET') {
                // Po zalogowaniu wrócimy tam, dokąd użytkownik chciał wejść.
                $this->session->set(self::INTENDED_URL_KEY, $request->path() . $request->queryString());
            }

            return new RedirectResponse('/login', 302);
        }

        // Strony z danymi użytkownika nie mogą trafić do cache przeglądarki: po wylogowaniu
        // przycisk Wstecz wymusi nowe żądanie, które skończy się przekierowaniem do logowania.
        return $next->handle($request)->withHeader('Cache-Control', 'no-store, private');
    }
}
