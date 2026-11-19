<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;

/**
 * Przepuszcza żądanie przez kolejne middleware, a na końcu przekazuje je do docelowego handlera.
 */
final class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private readonly array $middleware,
        private readonly RequestHandlerInterface $handler,
    ) {
    }

    public function handle(Request $request): Response
    {
        if ($this->middleware === []) {
            return $this->handler->handle($request);
        }

        return $this->middleware[0]->process($request, new self(array_slice($this->middleware, 1), $this->handler));
    }
}
