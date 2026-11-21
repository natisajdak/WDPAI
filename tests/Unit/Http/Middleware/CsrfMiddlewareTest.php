<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Exception\ForbiddenException;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Request;
use App\Security\Csrf;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ArraySession;
use Tests\Fake\FixedResponseHandler;

final class CsrfMiddlewareTest extends TestCase
{
    private Csrf $csrf;

    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        $this->csrf = new Csrf(new ArraySession());
        $this->middleware = new CsrfMiddleware($this->csrf);
    }

    public function testGetRequestPassesWithoutToken(): void
    {
        $handler = new FixedResponseHandler();

        $this->middleware->process(new Request('GET', '/'), $handler);

        self::assertNotNull($handler->received);
    }

    public function testPostWithValidFormTokenPasses(): void
    {
        $handler = new FixedResponseHandler();

        $this->middleware->process(new Request('POST', '/logout', body: [Csrf::FIELD_NAME => $this->csrf->token()]), $handler);

        self::assertNotNull($handler->received);
    }

    public function testPostWithValidHeaderTokenPasses(): void
    {
        $handler = new FixedResponseHandler();
        $request = new Request('POST', '/api/reservations', headers: ['x-csrf-token' => $this->csrf->token()]);

        $this->middleware->process($request, $handler);

        self::assertNotNull($handler->received);
    }

    public function testPostWithoutTokenIsForbidden(): void
    {
        $this->csrf->token();
        $handler = new FixedResponseHandler();

        try {
            $this->middleware->process(new Request('POST', '/logout'), $handler);
            self::fail('Oczekiwano ForbiddenException.');
        } catch (ForbiddenException $e) {
            self::assertSame('error.csrf', $e->messageKey());
            self::assertNull($handler->received, 'Kontroler nie może zostać wywołany.');
        }
    }

    public function testPostWithWrongTokenIsForbidden(): void
    {
        $this->csrf->token();

        $this->expectException(ForbiddenException::class);

        $this->middleware->process(new Request('POST', '/logout', body: [Csrf::FIELD_NAME => 'podrobiony']), new FixedResponseHandler());
    }
}
