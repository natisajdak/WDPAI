<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Security\Csrf;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ArraySession;

final class CsrfTest extends TestCase
{
    public function testTokenIsStableWithinSession(): void
    {
        $csrf = new Csrf(new ArraySession());

        self::assertSame($csrf->token(), $csrf->token());
        self::assertSame(64, strlen($csrf->token()));
    }

    public function testValidatesOnlyMatchingToken(): void
    {
        $csrf = new Csrf(new ArraySession());
        $token = $csrf->token();

        self::assertTrue($csrf->isValid($token));
        self::assertFalse($csrf->isValid('x' . substr($token, 1)));
        self::assertFalse($csrf->isValid(null));
        self::assertFalse($csrf->isValid(['tablica']));
    }

    public function testNothingIsValidBeforeTokenWasIssued(): void
    {
        self::assertFalse((new Csrf(new ArraySession()))->isValid(''));
    }

    public function testRegenerateInvalidatesOldToken(): void
    {
        $csrf = new Csrf(new ArraySession());
        $old = $csrf->token();

        $new = $csrf->regenerate();

        self::assertNotSame($old, $new);
        self::assertFalse($csrf->isValid($old));
        self::assertTrue($csrf->isValid($new));
    }
}
