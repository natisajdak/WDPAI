<?php

declare(strict_types=1);

namespace App\I18n;

use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Tłumaczenia z katalogów lang/{locale}.php (płaskie tablice klucz => tekst).
 * Brakujący klucz szuka w języku domyślnym, a jeśli i tam go nie ma, zwraca sam klucz, żeby był widoczny w UI.
 */
final class Translator
{
    /** @var array<string, array<string, string>> */
    private array $catalogs = [];

    private string $locale;

    /**
     * @param list<string> $supportedLocales
     */
    public function __construct(
        private readonly string $langPath,
        private readonly string $defaultLocale,
        private readonly array $supportedLocales,
    ) {
        if (!in_array($defaultLocale, $supportedLocales, true)) {
            throw new InvalidArgumentException(sprintf('Język domyślny %s nie jest obsługiwany.', $defaultLocale));
        }

        $this->locale = $defaultLocale;
    }

    public function setLocale(string $locale): void
    {
        if (!$this->supports($locale)) {
            throw new InvalidArgumentException(sprintf('Nieobsługiwany język: %s.', $locale));
        }

        $this->locale = $locale;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function defaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * @return list<string>
     */
    public function supportedLocales(): array
    {
        return $this->supportedLocales;
    }

    public function supports(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales, true);
    }

    public function has(string $key, ?string $locale = null): bool
    {
        return isset($this->catalog($locale ?? $this->locale)[$key]);
    }

    /**
     * @param array<string, string|int|float> $params wartości dla znaczników :name w tekście
     */
    public function trans(string $key, array $params = [], ?string $locale = null): string
    {
        $text = $this->catalog($locale ?? $this->locale)[$key]
            ?? $this->catalog($this->defaultLocale)[$key]
            ?? $key;

        // Dłuższe nazwy najpierw, żeby :max nie zostało podmienione w środku :maximum.
        uksort($params, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($params as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    /**
     * @return array<string, string>
     */
    public function catalog(string $locale): array
    {
        if (!$this->supports($locale)) {
            throw new InvalidArgumentException(sprintf('Nieobsługiwany język: %s.', $locale));
        }

        if (!isset($this->catalogs[$locale])) {
            $catalog = require $this->langPath . '/' . $locale . '.php';

            if (!is_array($catalog)) {
                throw new UnexpectedValueException(sprintf('Plik lang/%s.php musi zwracać tablicę.', $locale));
            }

            $this->catalogs[$locale] = $catalog;
        }

        return $this->catalogs[$locale];
    }
}
