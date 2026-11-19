<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/**
 * Middleware przyjmujące argumenty z definicji trasy, np. RoleMiddleware::class . ':ADMIN'.
 */
interface ConfigurableMiddlewareInterface extends MiddlewareInterface
{
    public function withArguments(string ...$arguments): static;
}
