<?php

declare(strict_types=1);

namespace App\Mail;

final class MailMessage
{
    public function __construct(
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $htmlBody,
        public readonly string $textBody,
    ) {
    }
}
