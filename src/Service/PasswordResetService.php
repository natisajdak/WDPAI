<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Clock;
use App\Database\TransactionRunner;
use App\Domain\Entity\PasswordResetToken;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidPasswordResetTokenException;
use App\Mail\MailComposer;
use App\Mail\MailerInterface;
use App\Mail\MailException;
use App\Repository\Contract\PasswordResetTokenRepositoryInterface;
use App\Repository\Contract\UserRepositoryInterface;
use App\Security\PasswordHasher;

/**
 * Reset hasła linkiem z jednorazowym tokenem. W bazie jest tylko SHA-256 tokenu,
 * więc wyciek bazy nie pozwala przejąć konta przez niewykorzystany link.
 */
final class PasswordResetService
{
    public const TOKEN_TTL_MINUTES = 60;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordResetTokenRepositoryInterface $tokens,
        private readonly PasswordHasher $hasher,
        private readonly MailerInterface $mailer,
        private readonly MailComposer $composer,
        private readonly TransactionRunner $transactions,
        private readonly Clock $clock,
        private readonly string $appUrl,
    ) {
    }

    /**
     * Dla nieznanego lub nieaktywnego adresu nic nie robi: odpowiedź dla użytkownika jest zawsze ta sama,
     * więc formularza nie da się użyć do sprawdzania, kto ma konto.
     */
    public function requestReset(string $email): void
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));

        if ($user === null || !$user->isActive) {
            return;
        }

        $this->sendLink($user);
    }

    public function isTokenValid(string $token): bool
    {
        return $this->findUsableToken($token) !== null;
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $record = $this->findUsableToken($token) ?? throw new InvalidPasswordResetTokenException();
        $now = $this->clock->now();

        $this->transactions->transactional(function () use ($record, $newPassword, $now): void {
            $this->users->updatePasswordHash($record->userId, $this->hasher->hash($newPassword));
            $this->tokens->markUsed($record->id, $now);
            $this->tokens->invalidateForUser($record->userId, $now);
        });
    }

    private function sendLink(User $user): void
    {
        $now = $this->clock->now();
        $token = bin2hex(random_bytes(32));

        $this->transactions->transactional(function () use ($user, $token, $now): void {
            $this->tokens->invalidateForUser($user->id, $now);
            $this->tokens->create($user->id, hash('sha256', $token), $now->modify(sprintf('+%d minutes', self::TOKEN_TTL_MINUTES)));
        });

        $message = $this->composer->compose(
            $user->email,
            $user->fullName(),
            $user->locale,
            'mail.password_reset.subject',
            'emails/password-reset',
            [
                'name' => $user->firstName,
                'link' => rtrim($this->appUrl, '/') . '/reset-password?token=' . $token,
                'minutes' => self::TOKEN_TTL_MINUTES,
            ],
        );

        try {
            $this->mailer->send($message);
        } catch (MailException $e) {
            // Użytkownik dostaje ten sam komunikat; błąd trafia do logu, a link można wysłać ponownie.
            error_log($e->getMessage());
        }
    }

    private function findUsableToken(string $token): ?PasswordResetToken
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return null;
        }

        $record = $this->tokens->findByHash(hash('sha256', $token));

        return $record !== null && $record->isUsable($this->clock->now()) ? $record : null;
    }
}
