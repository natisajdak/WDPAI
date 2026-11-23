<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Entity\User;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;

/**
 * Strony tylko dla niezalogowanych (logowanie, reset hasła). Zalogowany użytkownik trafia na dashboard.
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($request->attribute(CurrentUserMiddleware::ATTRIBUTE) instanceof User) {
            return new RedirectResponse('/dashboard', 302);
        }

        return $next->handle($request);
    }
}
