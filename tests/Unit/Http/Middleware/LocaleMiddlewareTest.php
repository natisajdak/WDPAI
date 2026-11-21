<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\LocaleMiddleware;
use App\Http\Request;
use App\I18n\Translator;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ArraySession;
use Tests\Fake\FixedResponseHandler;

final class LocaleMiddlewareTest extends TestCase
{
    private Translator $translator;

    private ArraySession $session;

    protected function setUp(): void
    {
        $this->translator = new Translator(dirname(__DIR__, 4) . '/lang', 'pl', ['pl', 'en']);
        $this->session = new ArraySession();
    }

    public function testSessionChoiceWins(): void
    {
        $this->session->set(LocaleMiddleware::SESSION_KEY, 'en');

        $this->process(['accept-language' => 'pl-PL,pl;q=0.9']);

        self::assertSame('en', $this->translator->locale());
    }

    public function testUsesHighestWeightedSupportedLanguage(): void
    {
        $this->process(['accept-language' => 'de-DE,de;q=0.9,en;q=0.8,pl;q=0.5']);

        self::assertSame('en', $this->translator->locale());
    }

    public function testFallsBackToDefaultLocale(): void
    {
        $this->process(['accept-language' => 'fr-FR,de;q=0.8']);

        self::assertSame('pl', $this->translator->locale());
    }

    public function testIgnoresUnsupportedLocaleInSession(): void
    {
        $this->session->set(LocaleMiddleware::SESSION_KEY, 'xx');

        $this->process([]);

        self::assertSame('pl', $this->translator->locale());
    }

    /**
     * @param array<string, string> $headers
     */
    private function process(array $headers): void
    {
        (new LocaleMiddleware($this->translator, $this->session))->process(new Request('GET', '/', headers: $headers), new FixedResponseHandler());
    }
}
