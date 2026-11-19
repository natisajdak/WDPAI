<?php

declare(strict_types=1);

namespace App\Http\Router;

use App\Http\Exception\MethodNotAllowedException;
use App\Http\Exception\NotFoundException;
use App\Http\Request;

final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    private string $prefix = '';

    /** @var list<string> */
    private array $groupMiddleware = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /**
     * Grupa tras ze wspólnym prefiksem i middleware, np. /admin dostępne tylko dla roli ADMIN.
     *
     * @param list<string> $middleware
     * @param callable(self): void $routes
     */
    public function group(string $prefix, array $middleware, callable $routes): void
    {
        [$previousPrefix, $previousMiddleware] = [$this->prefix, $this->groupMiddleware];

        $this->prefix .= $prefix;
        $this->groupMiddleware = [...$this->groupMiddleware, ...$middleware];

        try {
            $routes($this);
        } finally {
            [$this->prefix, $this->groupMiddleware] = [$previousPrefix, $previousMiddleware];
        }
    }

    public function match(string $method, string $path): RouteMatch
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $params = $route->match($path);

            if ($params === null) {
                continue;
            }

            if ($route->method === $method || ($method === 'HEAD' && $route->method === 'GET')) {
                return new RouteMatch($route, $params);
            }

            $allowedMethods[] = $route->method;
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowedMethods)));
        }

        throw new NotFoundException();
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $this->routes[] = new Route(
            $method,
            Request::normalizePath($this->prefix . $pattern),
            $handler,
            [...$this->groupMiddleware, ...$middleware],
        );
    }
}
