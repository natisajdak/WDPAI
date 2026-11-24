<?php

declare(strict_types=1);

namespace Tests\Unit\I18n;

use App\I18n\Translator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        $this->translator = new Translator(dirname(__DIR__, 3) . '/lang', 'pl', ['pl', 'en']);
    }

    public function testTranslatesInCurrentLocale(): void
    {
        self::assertSame('Pulpit', $this->translator->trans('nav.dashboard'));

        $this->translator->setLocale('en');

        self::assertSame('Dashboard', $this->translator->trans('nav.dashboard'));
    }

    public function testReplacesPlaceholders(): void
    {
        self::assertSame('Podaj liczbę od 1 do 10.', $this->translator->trans('validation.int_range', ['min' => 1, 'max' => 10]));
    }

    public function testMissingKeyReturnsKeyItself(): void
    {
        self::assertSame('nie.ma.takiego.klucza', $this->translator->trans('nie.ma.takiego.klucza'));
    }

    public function testRejectsUnsupportedLocale(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->translator->setLocale('de');
    }

    public function testCatalogsHaveIdenticalKeys(): void
    {
        $polish = array_keys($this->translator->catalog('pl'));
        $english = array_keys($this->translator->catalog('en'));

        self::assertSame([], array_values(array_diff($polish, $english)), 'Klucze brakujące w lang/en.php');
        self::assertSame([], array_values(array_diff($english, $polish)), 'Klucze brakujące w lang/pl.php');
    }
}
