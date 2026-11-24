<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\I18n\Translator;
use App\Security\Authorization;
use App\Security\ContentSecurityPolicy;
use App\Security\Csrf;
use App\View\View;

/**
 * Prawdziwy View i Translator na szablonach i katalogach z repozytorium, bez sesji PHP.
 */
final class TestViewFactory
{
    public static function translator(): Translator
    {
        return new Translator(self::root() . '/lang', 'pl', ['pl', 'en']);
    }

    public static function view(Translator $translator): View
    {
        $session = new ArraySession();

        return new View(
            self::root() . '/templates',
            self::root() . '/public',
            $translator,
            new Csrf($session),
            new ContentSecurityPolicy(),
            new Authorization(),
            $session,
        );
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
