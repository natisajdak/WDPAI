<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Container;
use App\Http\Middleware\MiddlewareFactory;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Router\Router;

/**
 * Dopasowuje trasę, uruchamia jej middleware (np. Auth, Role), a następnie kontroler.
 */
final class RouteDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private readonly Router $router,
        private readonly MiddlewareFactory $middlewareFactory,
        private readonly Container $container,
    ) {
    }

    public function handle(Request $request): Response
    {
        $match = $this->router->match($request->method(), $request->path());

        $pipeline = new MiddlewarePipeline(
            $this->middlewareFactory->createMany($match->route->middleware),
            new ControllerHandler($this->container, $match->route->handler),
        );

        return $pipeline->handle($request->withRouteParams($match->params));
    }
}
