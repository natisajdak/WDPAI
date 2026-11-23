<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use App\Service\AuthService;
use App\View\View;

/**
 * Odtwarza zalogowanego użytkownika z sesji (o ile jest) i udostępnia go dalszym warstwom oraz widokom.
 * Nie blokuje dostępu: robią to AuthMiddleware i RoleMiddleware na konkretnych trasach.
 */
final class CurrentUserMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE = 'user';

    public function __construct(
        private readonly AuthService $auth,
        private readonly View $view,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $user = $this->auth->currentUser();

        if ($user !== null) {
            $request = $request->withAttribute(self::ATTRIBUTE, $user);
            $this->view->share('currentUser', $user);
        }

        return $next->handle($request);
    }
}
