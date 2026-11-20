<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Exception\NotFoundException;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Http\Response;
use App\Http\SafeRedirect;
use App\I18n\Translator;
use App\Security\SessionInterface;

final class LocaleController
{
    public function __construct(
        private readonly Translator $translator,
        private readonly SessionInterface $session,
    ) {
    }

    /**
     * GET /lang/{locale:pl|en}: zapamiętuje język w sesji i wraca na poprzednią stronę.
     */
    public function switch(Request $request): Response
    {
        $locale = $request->param('locale');

        // Wzorzec trasy dopuszcza tylko pl|en; sprawdzamy jeszcze raz na wypadek zmiany trasy.
        if (!$this->translator->supports($locale)) {
            throw new NotFoundException();
        }

        $this->session->set(LocaleMiddleware::SESSION_KEY, $locale);

        return new RedirectResponse(SafeRedirect::back($request), 302);
    }
}
