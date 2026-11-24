<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\User;
use App\Http\Middleware\CurrentUserMiddleware;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Http\Response;

final class HomeController
{
    /**
     * Strona główna nie ma własnej treści: zalogowany trafia na dashboard, pozostali do logowania.
     */
    public function index(Request $request): Response
    {
        $loggedIn = $request->attribute(CurrentUserMiddleware::ATTRIBUTE) instanceof User;

        return new RedirectResponse($loggedIn ? '/dashboard' : '/login', 302);
    }
}
