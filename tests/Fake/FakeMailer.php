<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Mail\MailerInterface;
use App\Mail\MailException;
use App\Mail\MailMessage;

final class FakeMailer implements MailerInterface
{
    /** @var list<MailMessage> */
    public array $sent = [];

    public bool $failing = false;

    public function send(MailMessage $message): void
    {
        if ($this->failing) {
            throw new MailException('SMTP niedostępny');
        }

        $this->sent[] = $message;
    }
}
