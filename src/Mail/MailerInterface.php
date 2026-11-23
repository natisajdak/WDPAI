<?php

declare(strict_types=1);

namespace App\Mail;

interface MailerInterface
{
    /**
     * @throws MailException gdy serwer SMTP odrzuci wiadomość albo jest nieosiągalny
     */
    public function send(MailMessage $message): void;
}
