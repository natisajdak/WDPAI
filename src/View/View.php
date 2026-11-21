<?php

declare(strict_types=1);

namespace App\View;

use App\I18n\Translator;
use App\Security\ContentSecurityPolicy;
use App\Security\Csrf;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Renderer szablonów PHP z katalogu templates/. Wewnątrz szablonu $this wskazuje na ten obiekt.
 *
 * Zasada kodowania wyjścia: metody zwracające tekst do wstawienia w HTML (t, url, asset, csrfField…)
 * zwracają go już zakodowanego. Dane z bazy i od użytkownika wypisujemy zawsze przez e().
 */
final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(
        private readonly string $templatesPath,
        private readonly string $publicPath,
        private readonly Translator $translator,
        private readonly Csrf $csrf,
        private readonly ContentSecurityPolicy $csp,
    ) {
    }

    /**
     * Dane dostępne we wszystkich szablonach, np. zalogowany użytkownik.
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?string $layout = 'layout/base'): string
    {
        $content = $this->renderFile($template, $data);

        return $layout === null ? $content : $this->renderFile($layout, ['content' => $content] + $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->renderFile($template, $data);
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param array<string, string|int|float> $params
     */
    public function t(string $key, array $params = []): string
    {
        return $this->e($this->translator->trans($key, $params));
    }

    public function locale(): string
    {
        return $this->translator->locale();
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return $this->translator->supportedLocales();
    }

    /**
     * @param array<string, string|int|null> $query puste wartości są pomijane
     */
    public function url(string $path, array $query = []): string
    {
        $query = array_filter($query, static fn (string|int|null $value): bool => $value !== null && $value !== '');

        return $this->e($path . ($query === [] ? '' : '?' . http_build_query($query)));
    }

    /**
     * Adres pliku z public/assets z wersją zależną od daty modyfikacji (przeglądarka pobierze nową wersję po zmianie).
     */
    public function asset(string $path): string
    {
        $file = $this->publicPath . '/assets/' . ltrim($path, '/');
        $version = is_file($file) ? (string) filemtime($file) : '0';

        return $this->e('/assets/' . ltrim($path, '/') . '?v=' . $version);
    }

    public function csrfToken(): string
    {
        return $this->e($this->csrf->token());
    }

    public function csrfField(): string
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', Csrf::FIELD_NAME, $this->csrfToken());
    }

    public function cspNonce(): string
    {
        return $this->e($this->csp->nonce());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $template, array $data): string
    {
        if (preg_match('#^[a-z0-9_-]+(/[a-z0-9_-]+)*$#', $template) !== 1) {
            throw new InvalidArgumentException(sprintf('Niepoprawna nazwa szablonu: %s.', $template));
        }

        $file = $this->templatesPath . '/' . $template . '.php';

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Brak szablonu %s.', $template));
        }

        $level = ob_get_level();
        ob_start();

        try {
            $this->includeTemplate($file, $data + $this->shared);

            return (string) ob_get_clean();
        } catch (Throwable $e) {
            // Porzucamy częściowo wyrenderowany HTML, żeby nie wymieszał się ze stroną błędu.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $__data
     */
    private function includeTemplate(string $__file, array $__data): void
    {
        extract($__data, EXTR_SKIP);

        require $__file;
    }
}
