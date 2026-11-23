<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Entity\User;
use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use App\I18n\Translator;
use App\Security\SessionInterface;

/**
 * Ustala język interfejsu: wybór zapisany w sesji, potem język z profilu zalogowanego użytkownika,
 * potem nagłówek Accept-Language, na końcu język domyślny.
 */
final class LocaleMiddleware implements MiddlewareInterface
{
    public const SESSION_KEY = 'locale';

    public function __construct(
        private readonly Translator $translator,
        private readonly SessionInterface $session,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->translator->setLocale($this->resolve($request));

        return $next->handle($request);
    }

    private function resolve(Request $request): string
    {
        $chosen = $this->session->get(self::SESSION_KEY);

        if (is_string($chosen) && $this->translator->supports($chosen)) {
            return $chosen;
        }

        $user = $request->attribute(CurrentUserMiddleware::ATTRIBUTE);

        if ($user instanceof User && $this->translator->supports($user->locale)) {
            return $user->locale;
        }

        return $this->fromAcceptLanguage($request->header('accept-language') ?? '') ?? $this->translator->defaultLocale();
    }

    /**
     * Np. "en-GB,en;q=0.9,pl;q=0.8" → "en". Języki sortujemy według wagi q.
     */
    private function fromAcceptLanguage(string $header): ?string
    {
        $candidates = [];

        foreach (explode(',', $header) as $index => $part) {
            [$tag, $quality] = array_pad(explode(';q=', trim($part), 2), 2, '1');
            $language = strtolower(substr(trim($tag), 0, 2));

            if ($this->translator->supports($language)) {
                // Indeks rozstrzyga remisy na korzyść języka podanego wcześniej.
                $candidates[] = ['language' => $language, 'quality' => (float) $quality, 'index' => $index];
            }
        }

        usort($candidates, static fn (array $a, array $b): int => [$b['quality'], $a['index']] <=> [$a['quality'], $b['index']]);

        return $candidates[0]['language'] ?? null;
    }
}
