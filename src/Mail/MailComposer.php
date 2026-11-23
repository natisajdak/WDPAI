<?php

declare(strict_types=1);

namespace App\Mail;

use App\I18n\Translator;
use App\View\View;

/**
 * Buduje wiadomość z szablonu templates/emails/* w języku odbiorcy (wersja HTML i tekstowa).
 */
final class MailComposer
{
    public function __construct(
        private readonly View $view,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function compose(
        string $toEmail,
        string $toName,
        string $locale,
        string $subjectKey,
        string $template,
        array $data = [],
    ): MailMessage {
        return $this->translator->withLocale($locale, function () use ($toEmail, $toName, $subjectKey, $template, $data): MailMessage {
            $subject = $this->translator->trans($subjectKey);
            $html = $this->view->render($template, ['subject' => $subject] + $data, 'emails/layout');

            return new MailMessage($toEmail, $toName, $subject, $html, $this->toText($html));
        });
    }

    /**
     * Wersja tekstowa dla klientów bez HTML: linki zamieniamy na „tekst: adres”.
     */
    private function toText(string $html): string
    {
        $body = preg_replace('#^.*<body[^>]*>|</body>.*$#si', '', $html) ?? $html;
        $body = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#si', '$2: $1', $body) ?? $body;
        $body = preg_replace('#<(br|/p|/h[1-6]|/li)\s*/?>#i', "\n", $body) ?? $body;
        $text = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/[ \t]+/", ' ', preg_replace("/\n\s*\n\s*/", "\n\n", $text) ?? $text) ?? $text);
    }
}
