<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Clock;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\TooManyLoginAttemptsException;
use App\Repository\Contract\LoginAttemptRepositoryInterface;
use App\Repository\Contract\UserRepositoryInterface;
use App\Security\Csrf;
use App\Security\PasswordHasher;
use App\Security\SessionInterface;

/**
 * Logowanie, odtwarzanie zalogowanego użytkownika z sesji i wylogowanie.
 * W sesji trzymamy tylko id użytkownika; rola i status konta są czytane z bazy przy każdym żądaniu.
 */
final class AuthService
{
    public const MAX_FAILED_ATTEMPTS = 5;
    public const THROTTLE_MINUTES = 15;

    private const SESSION_USER_KEY = 'user_id';

    private ?User $currentUser = null;

    private bool $resolved = false;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly LoginAttemptRepositoryInterface $attempts,
        private readonly PasswordHasher $hasher,
        private readonly SessionInterface $session,
        private readonly Csrf $csrf,
        private readonly Clock $clock,
    ) {
    }

    public function attempt(string $email, string $password, string $ipAddress): User
    {
        $email = mb_strtolower(trim($email));
        $now = $this->clock->now();
        $windowStart = $now->modify(sprintf('-%d minutes', self::THROTTLE_MINUTES));

        if ($this->attempts->countFailuresSince($email, $ipAddress, $windowStart) >= self::MAX_FAILED_ATTEMPTS) {
            throw new TooManyLoginAttemptsException(self::THROTTLE_MINUTES);
        }

        $user = $this->users->findByEmail($email);

        // Hasło weryfikujemy zawsze, także gdy konta nie ma (patrz PasswordHasher::DUMMY_HASH).
        $passwordMatches = $this->hasher->verify($password, $user?->passwordHash);

        if ($user === null || !$passwordMatches || !$user->canSignIn()) {
            $this->attempts->record($email, $ipAddress, false, $now);

            throw new InvalidCredentialsException();
        }

        $this->attempts->record($email, $ipAddress, true, $now);

        if ($this->hasher->needsRehash((string) $user->passwordHash)) {
            $this->users->updatePasswordHash($user->id, $this->hasher->hash($password));
        }

        $this->signIn($user);

        return $user;
    }

    public function currentUser(): ?User
    {
        if ($this->resolved) {
            return $this->currentUser;
        }

        $this->resolved = true;
        $userId = $this->session->get(self::SESSION_USER_KEY);

        if (!is_int($userId)) {
            return null;
        }

        $user = $this->users->findById($userId);

        // Konto dezaktywowane przez kierownika traci dostęp przy najbliższym żądaniu (R11).
        if ($user === null || !$user->isActive) {
            $this->session->remove(self::SESSION_USER_KEY);

            return null;
        }

        return $this->currentUser = $user;
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->currentUser = null;
        $this->resolved = true;
    }

    private function signIn(User $user): void
    {
        // Nowy identyfikator sesji i nowy token CSRF: wartości sprzed logowania przestają działać (session fixation).
        $this->session->regenerate();
        $this->session->set(self::SESSION_USER_KEY, $user->id);
        $this->csrf->regenerate();
        $this->users->updateLastLogin($user->id, $this->clock->now());

        $this->currentUser = $user;
        $this->resolved = true;
    }
}
