<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Container;
use LogicException;

/**
 * Ostatni element łańcucha: tworzy kontroler z kontenera i wywołuje jego akcję.
 */
final class ControllerHandler implements RequestHandlerInterface
{
    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $action,
    ) {
    }

    public function handle(Request $request): Response
    {
        [$class, $method] = $this->action;
        $controller = $this->container->get($class);

        if (!method_exists($controller, $method)) {
            throw new LogicException(sprintf('Kontroler %s nie ma akcji %s().', $class, $method));
        }

        $response = $controller->$method($request);

        if (!$response instanceof Response) {
            throw new LogicException(sprintf('Akcja %s::%s() musi zwrócić Response.', $class, $method));
        }

        return $response;
    }
}
