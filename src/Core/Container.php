<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;

/**
 * Prosty kontener zależności.
 *
 * Usługi wymagające konfiguracji (np. połączenie z bazą) rejestrujemy fabryką w config/services.php.
 * Pozostałe klasy (kontrolery, serwisy, repozytoria) są tworzone automatycznie na podstawie typów
 * parametrów konstruktora. Każda usługa powstaje raz na żądanie.
 */
final class Container
{
    /** @var array<string, callable(self): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, true> */
    private array $resolving = [];

    /**
     * @param callable(self): object $factory
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * Wiąże interfejs z implementacją, która sama zostanie utworzona automatycznie.
     *
     * @param class-string $abstract
     * @param class-string $concrete
     */
    public function bind(string $abstract, string $concrete): void
    {
        $this->set($abstract, static fn (self $container): object => $container->get($concrete));
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException(sprintf('Cykliczna zależność przy tworzeniu %s.', $id));
        }

        $this->resolving[$id] = true;

        try {
            $instance = isset($this->factories[$id]) ? ($this->factories[$id])($this) : $this->autowire($id);
        } finally {
            unset($this->resolving[$id]);
        }

        if (!$instance instanceof $id) {
            throw new ContainerException(sprintf('Fabryka %s zwróciła obiekt klasy %s.', $id, $instance::class));
        }

        return $this->instances[$id] = $instance;
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException(sprintf('Brak definicji usługi %s w config/services.php.', $class));
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('Klasy %s nie można utworzyć automatycznie.', $class));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string $dependency */
                $dependency = $type->getName();
                $arguments[] = $this->get($dependency);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerException(sprintf(
                'Nie można automatycznie utworzyć %s: parametr $%s wymaga fabryki w config/services.php.',
                $class,
                $parameter->getName(),
            ));
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
