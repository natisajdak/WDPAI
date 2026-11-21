<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\RedirectResponse;
use App\Http\Response;
use App\View\View;

/**
 * Wspólne skróty kontrolerów HTML. Kontroler tylko odczytuje żądanie, wywołuje serwis i zwraca odpowiedź.
 */
abstract class AbstractController
{
    public function __construct(protected readonly View $view)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = [], int $status = 200): Response
    {
        return new Response($this->view->render($template, $data), $status);
    }

    protected function json(mixed $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    protected function redirect(string $location): RedirectResponse
    {
        return new RedirectResponse($location);
    }
}
