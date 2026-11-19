<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Container;
use LogicException;

/**
 * Zamienia definicje z tras ("Klasa" albo "Klasa:arg1,arg2") na obiekty middleware.
 */
final class MiddlewareFactory
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param list<string> $definitions
     * @return list<MiddlewareInterface>
     */
    public function createMany(array $definitions): array
    {
        return array_map($this->create(...), $definitions);
    }

    public function create(string $definition): MiddlewareInterface
    {
        [$class, $arguments] = array_pad(explode(':', $definition, 2), 2, null);

        /** @var class-string $class */
        $middleware = $this->container->get($class);

        if (!$middleware instanceof MiddlewareInterface) {
            throw new LogicException(sprintf('%s nie jest middleware.', $class));
        }

        if ($arguments === null) {
            return $middleware;
        }

        if (!$middleware instanceof ConfigurableMiddlewareInterface) {
            throw new LogicException(sprintf('%s nie przyjmuje argumentów.', $class));
        }

        return $middleware->withArguments(...explode(',', $arguments));
    }
}
