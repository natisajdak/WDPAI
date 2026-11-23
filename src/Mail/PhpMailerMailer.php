<?php

declare(strict_types=1);

namespace App\Mail;

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Wysyłka przez SMTP. Dane serwera pochodzą ze zmiennych MAIL_* (lokalnie Mailpit bez szyfrowania i logowania).
 */
final class PhpMailerMailer implements MailerInterface
{
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {
    }

    public function send(MailMessage $message): void
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->Port = $this->port;
            $mail->Timeout = self::TIMEOUT_SECONDS;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            if ($this->username !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $this->username;
                $mail->Password = $this->password;
            }

            $mail->SMTPSecure = match ($this->encryption) {
                'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                default => '',
            };
            // Bez szyfrowania w konfiguracji (Mailpit) nie próbujemy też automatycznego STARTTLS.
            $mail->SMTPAutoTLS = $this->encryption !== '';

            $mail->setFrom($this->fromAddress, $this->fromName);
            $mail->addAddress($message->toEmail, $message->toName);
            $mail->isHTML();
            $mail->Subject = $message->subject;
            $mail->Body = $message->htmlBody;
            $mail->AltBody = $message->textBody;

            $mail->send();
        } catch (PhpMailerException $e) {
            throw new MailException(sprintf('Nie udało się wysłać e-maila do %s: %s', $message->toEmail, $mail->ErrorInfo), 0, $e);
        }
    }
}
