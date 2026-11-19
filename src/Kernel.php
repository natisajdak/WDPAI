<?php

declare(strict_types=1);

namespace App;

use App\Http\Middleware\MiddlewareFactory;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Request;
use App\Http\Response;
use App\Http\RouteDispatcher;
use Throwable;

/**
 * Punkt styku z PHP: przyjmuje żądanie, przepuszcza je przez globalne middleware i router, zwraca odpowiedź.
 */
final class Kernel
{
    /**
     * @param list<string> $globalMiddleware w kolejności od zewnętrznego do wewnętrznego
     */
    public function __construct(
        private readonly MiddlewareFactory $middlewareFactory,
        private readonly RouteDispatcher $dispatcher,
        private readonly array $globalMiddleware,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $pipeline = new MiddlewarePipeline($this->middlewareFactory->createMany($this->globalMiddleware), $this->dispatcher);

            return $pipeline->handle($request);
        } catch (Throwable $e) {
            // Ostatnia linia obrony, np. gdy nie da się wyrenderować nawet strony błędu.
            error_log(sprintf('[%s %s] %s', $request->method(), $request->path(), $e));

            return new Response('Internal Server Error', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
    }
}
