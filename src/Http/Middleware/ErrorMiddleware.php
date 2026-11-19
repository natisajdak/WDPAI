<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\ErrorResponder;
use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use Throwable;

/**
 * Zamienia każdy wyjątek z dalszej części łańcucha na kontrolowaną odpowiedź (strona błędu albo JSON).
 */
final class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ErrorResponder $responder)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        try {
            return $next->handle($request);
        } catch (Throwable $e) {
            return $this->responder->respond($e, $request);
        }
    }
}
