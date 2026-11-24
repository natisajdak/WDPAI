<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\TooManyLoginAttemptsException;
use App\Security\Csrf;
use App\Security\PasswordHasher;
use App\Service\AuthService;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ArraySession;
use Tests\Fake\FrozenClock;
use Tests\Fake\InMemoryLoginAttemptRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\TestUsers;

final class AuthServiceTest extends TestCase
{
    private const IP = '192.0.2.10';

    private InMemoryUserRepository $users;

    private InMemoryLoginAttemptRepository $attempts;

    private ArraySession $session;

    private Csrf $csrf;

    private FrozenClock $clock;

    private AuthService $auth;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository(
            TestUsers::make(id: 1, email: 'operator@cargo.local'),
            TestUsers::make(id: 2, email: 'nieaktywny@cargo.local', isActive: false),
            TestUsers::make(id: 3, email: 'nowy@cargo.local', hasPassword: false),
        );
        $this->attempts = new InMemoryLoginAttemptRepository();
        $this->session = new ArraySession();
        $this->csrf = new Csrf($this->session);
        $this->clock = new FrozenClock();
        $this->auth = new AuthService($this->users, $this->attempts, new PasswordHasher(), $this->session, $this->csrf, $this->clock);
    }

    public function testCorrectCredentialsSignInAndRegenerateSession(): void
    {
        $oldCsrfToken = $this->csrf->token();

        $user = $this->auth->attempt('  Operator@Cargo.local ', TestUsers::PASSWORD, self::IP);

        self::assertSame(1, $user->id);
        self::assertSame(1, $this->session->get('user_id'));
        self::assertSame(1, $this->session->regenerations, 'Po zalogowaniu identyfikator sesji musi się zmienić.');
        self::assertFalse($this->csrf->isValid($oldCsrfToken), 'Token CSRF sprzed logowania musi przestać działać.');
        self::assertEquals($this->clock->now(), $this->users->lastLogins[1]);
        self::assertSame($user, $this->auth->currentUser());
    }

    public function testWrongPasswordIsRejectedAndRecorded(): void
    {
        try {
            $this->auth->attempt('operator@cargo.local', 'zle-haslo', self::IP);
            self::fail('Oczekiwano InvalidCredentialsException.');
        } catch (InvalidCredentialsException) {
            self::assertNull($this->session->get('user_id'));
            self::assertFalse($this->attempts->attempts[0]['succeeded']);
        }
    }

    public function testUnknownEmailGivesSameErrorAsWrongPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->auth->attempt('nie-ma@cargo.local', TestUsers::PASSWORD, self::IP);
    }

    public function testInactiveAccountCannotSignInEvenWithCorrectPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->auth->attempt('nieaktywny@cargo.local', TestUsers::PASSWORD, self::IP);
    }

    public function testAccountWithoutPasswordCannotSignIn(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->auth->attempt('nowy@cargo.local', '', self::IP);
    }

    public function testBlocksAfterTooManyFailuresWithinWindow(): void
    {
        for ($i = 0; $i < AuthService::MAX_FAILED_ATTEMPTS; $i++) {
            try {
                $this->auth->attempt('operator@cargo.local', 'zle-haslo', self::IP);
            } catch (InvalidCredentialsException) {
            }
        }

        try {
            $this->auth->attempt('operator@cargo.local', TestUsers::PASSWORD, self::IP);
            self::fail('Oczekiwano blokady mimo poprawnego hasła.');
        } catch (TooManyLoginAttemptsException $e) {
            self::assertSame(AuthService::THROTTLE_MINUTES, $e->retryAfterMinutes);
        }

        $this->clock->advance(sprintf('+%d minutes', AuthService::THROTTLE_MINUTES + 1));

        self::assertSame(1, $this->auth->attempt('operator@cargo.local', TestUsers::PASSWORD, self::IP)->id);
    }

    public function testCurrentUserIsRestoredFromSession(): void
    {
        $this->session->set('user_id', 1);

        self::assertSame(1, $this->auth->currentUser()?->id);
    }

    public function testDeactivatedUserLosesSessionOnNextRequest(): void
    {
        $this->session->set('user_id', 2);

        self::assertNull($this->auth->currentUser());
        self::assertNull($this->session->get('user_id'));
    }

    public function testLogoutDestroysSession(): void
    {
        $this->auth->attempt('operator@cargo.local', TestUsers::PASSWORD, self::IP);

        $this->auth->logout();

        self::assertTrue($this->session->destroyed);
        self::assertNull($this->auth->currentUser());
    }
}
