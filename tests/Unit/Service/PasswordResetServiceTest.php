<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\Exception\InvalidPasswordResetTokenException;
use App\Mail\MailComposer;
use App\Security\PasswordHasher;
use App\Service\PasswordResetService;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeMailer;
use Tests\Fake\FrozenClock;
use Tests\Fake\ImmediateTransactionRunner;
use Tests\Fake\InMemoryPasswordResetTokenRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\TestUsers;
use Tests\Fake\TestViewFactory;

final class PasswordResetServiceTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryPasswordResetTokenRepository $tokens;

    private FakeMailer $mailer;

    private FrozenClock $clock;

    private PasswordResetService $service;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository(
            TestUsers::make(id: 1, email: 'operator@cargo.local', locale: 'en'),
            TestUsers::make(id: 2, email: 'nieaktywny@cargo.local', isActive: false),
        );
        $this->tokens = new InMemoryPasswordResetTokenRepository();
        $this->mailer = new FakeMailer();
        $this->clock = new FrozenClock();

        $translator = TestViewFactory::translator();

        $this->service = new PasswordResetService(
            $this->users,
            $this->tokens,
            new PasswordHasher(),
            $this->mailer,
            new MailComposer(TestViewFactory::view($translator), $translator),
            new ImmediateTransactionRunner(),
            $this->clock,
            'https://cargo.example',
        );
    }

    public function testSendsLinkInUserLanguageAndStoresOnlyTokenHash(): void
    {
        $this->service->requestReset('Operator@Cargo.local');

        self::assertCount(1, $this->mailer->sent);
        $message = $this->mailer->sent[0];
        self::assertSame('operator@cargo.local', $message->toEmail);
        self::assertSame('Cargo: set a new password', $message->subject);

        $token = $this->extractToken($message->htmlBody);
        self::assertArrayHasKey(hash('sha256', $token), $this->tokens->tokens, 'W bazie ma być hash, a nie sam token.');
        self::assertArrayNotHasKey($token, $this->tokens->tokens);
        self::assertStringContainsString('https://cargo.example/reset-password?token=' . $token, $message->textBody);
    }

    public function testUnknownOrInactiveEmailSendsNothing(): void
    {
        $this->service->requestReset('nie-ma@cargo.local');
        $this->service->requestReset('nieaktywny@cargo.local');

        self::assertSame([], $this->mailer->sent);
        self::assertSame([], $this->tokens->tokens);
    }

    public function testValidTokenChangesPasswordOnlyOnce(): void
    {
        $token = $this->requestToken();

        self::assertTrue($this->service->isTokenValid($token));

        $this->service->resetPassword($token, 'NoweHaslo123');

        self::assertTrue(password_verify('NoweHaslo123', $this->users->passwordHashes[1]));
        self::assertFalse($this->service->isTokenValid($token), 'Token jest jednorazowy.');

        $this->expectException(InvalidPasswordResetTokenException::class);
        $this->service->resetPassword($token, 'InneHaslo456');
    }

    public function testTokenExpiresAfterTtl(): void
    {
        $token = $this->requestToken();

        $this->clock->advance(sprintf('+%d minutes', PasswordResetService::TOKEN_TTL_MINUTES + 1));

        self::assertFalse($this->service->isTokenValid($token));
    }

    public function testNewRequestInvalidatesPreviousLink(): void
    {
        $first = $this->requestToken();
        $second = $this->requestToken();

        self::assertFalse($this->service->isTokenValid($first));
        self::assertTrue($this->service->isTokenValid($second));
    }

    public function testMalformedTokenIsRejectedWithoutLookup(): void
    {
        self::assertFalse($this->service->isTokenValid("' OR 1=1 --"));
    }

    public function testMailFailureDoesNotRevealAnything(): void
    {
        $this->mailer->failing = true;
        $this->expectErrorLog();

        $this->service->requestReset('operator@cargo.local');

        self::assertCount(1, $this->tokens->tokens, 'Token powstaje, a błąd wysyłki trafia tylko do logu.');
    }

    private function requestToken(): string
    {
        $this->service->requestReset('operator@cargo.local');

        return $this->extractToken(end($this->mailer->sent)->htmlBody);
    }

    private function extractToken(string $html): string
    {
        self::assertSame(1, preg_match('/token=([0-9a-f]{64})/', $html, $matches));

        return $matches[1];
    }
}
