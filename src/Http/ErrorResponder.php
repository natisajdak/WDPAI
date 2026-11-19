<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\HttpException;
use App\I18n\Translator;
use App\View\View;
use Throwable;

final class ErrorResponder
{
    private const KNOWN_STATUSES = [400, 401, 403, 404, 405, 500];

    public function __construct(
        private readonly View $view,
        private readonly Translator $translator,
        private readonly bool $debug,
    ) {
    }

    public function respond(Throwable $e, Request $request): Response
    {
        $status = $e instanceof HttpException ? $e->status() : 500;
        $headers = $e instanceof HttpException ? $e->headers() : [];

        if ($status >= 500) {
            // Szczegóły trafiają tylko do logu kontenera; użytkownik widzi ogólny komunikat.
            error_log(sprintf('[%s %s] %s', $request->method(), $request->path(), $e));
        }

        $message = $this->translator->trans($e instanceof HttpException ? $e->messageKey() : 'error.500.message');

        if ($request->wantsJson()) {
            $context = $e instanceof HttpException ? $e->context() : [];

            return new JsonResponse(['error' => ['code' => $status, 'message' => $message] + $context], $status, $headers);
        }

        $titleKey = in_array($status, self::KNOWN_STATUSES, true) ? sprintf('error.%d.title', $status) : 'error.generic.title';

        $html = $this->view->render('errors/error', [
            'title' => $this->translator->trans($titleKey),
            'status' => $status,
            'message' => $message,
            'details' => $this->debug && $status >= 500 ? sprintf("%s: %s\n\n%s", $e::class, $e->getMessage(), $e->getTraceAsString()) : null,
        ]);

        return new Response($html, $status, $headers);
    }
}
