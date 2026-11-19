<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;

/**
 * Warstwa przetwarzająca żądanie przed kontrolerem (i odpowiedź po nim).
 * Może przerwać łańcuch, zwracając własną odpowiedź, np. 403.
 */
interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response;
}
