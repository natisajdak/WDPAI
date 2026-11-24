<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\User;
use App\Http\Exception\NotFoundException;
use App\Http\Middleware\CurrentUserMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Http\Response;
use App\Http\SafeRedirect;
use App\I18n\Translator;
use App\Security\SessionInterface;
use App\Service\UserService;

final class LocaleController
{
    public function __construct(
        private readonly Translator $translator,
        private readonly SessionInterface $session,
        private readonly UserService $users,
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

        // Zalogowanemu zapamiętujemy wybór w profilu: e-maile też będzie dostawać w tym języku.
        $user = $request->attribute(CurrentUserMiddleware::ATTRIBUTE);

        if ($user instanceof User) {
            $this->users->changeLocale($user, $locale);
        }

        return new RedirectResponse(SafeRedirect::back($request), 302);
    }
}
