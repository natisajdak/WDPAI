<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use App\Security\SessionInterface;

final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->session->start();

        return $next->handle($request);
    }
}
