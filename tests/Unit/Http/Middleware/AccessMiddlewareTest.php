<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Domain\Enum\Role;
use App\Http\Exception\ForbiddenException;
use App\Http\Exception\UnauthorizedException;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CurrentUserMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Request;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ArraySession;
use Tests\Fake\FixedResponseHandler;
use Tests\Fake\TestUsers;

/**
 * AuthMiddleware, GuestMiddleware i RoleMiddleware: kto i jak zostaje zatrzymany przed kontrolerem.
 */
final class AccessMiddlewareTest extends TestCase
{
    public function testGuestOnHtmlPageIsRedirectedToLoginAndTargetIsRemembered(): void
    {
        $session = new ArraySession();
        $handler = new FixedResponseHandler();

        $response = (new AuthMiddleware($session))->process(new Request('GET', '/dashboard', ['tab' => 'faults']), $handler);

        self::assertSame(302, $response->status());
        self::assertSame('/login', $response->header('Location'));
        self::assertSame('/dashboard?tab=faults', $session->get(AuthMiddleware::INTENDED_URL_KEY));
        self::assertNull($handler->received);
    }

    public function testGuestOnApiGets401(): void
    {
        $this->expectException(UnauthorizedException::class);

        (new AuthMiddleware(new ArraySession()))->process(new Request('GET', '/api/me'), new FixedResponseHandler());
    }

    public function testSignedInUserPassesAndResponseIsNotCached(): void
    {
        $request = (new Request('GET', '/dashboard'))->withAttribute(CurrentUserMiddleware::ATTRIBUTE, TestUsers::make());

        $response = (new AuthMiddleware(new ArraySession()))->process($request, new FixedResponseHandler());

        self::assertSame(200, $response->status());
        self::assertSame('no-store, private', $response->header('Cache-Control'));
    }

    public function testOperatorOnAdminRouteGets403(): void
    {
        $request = (new Request('GET', '/admin/users'))->withAttribute(CurrentUserMiddleware::ATTRIBUTE, TestUsers::make(role: Role::Operator));
        $handler = new FixedResponseHandler();

        try {
            (new RoleMiddleware())->withArguments('ADMIN')->process($request, $handler);
            self::fail('Oczekiwano ForbiddenException.');
        } catch (ForbiddenException) {
            self::assertNull($handler->received);
        }
    }

    public function testAdminOnAdminRoutePasses(): void
    {
        $request = (new Request('GET', '/admin/users'))->withAttribute(CurrentUserMiddleware::ATTRIBUTE, TestUsers::make(role: Role::Admin));

        $response = (new RoleMiddleware())->withArguments('ADMIN')->process($request, new FixedResponseHandler());

        self::assertSame(200, $response->status());
    }

    public function testSignedInUserIsRedirectedAwayFromLoginPage(): void
    {
        $request = (new Request('GET', '/login'))->withAttribute(CurrentUserMiddleware::ATTRIBUTE, TestUsers::make());

        $response = (new GuestMiddleware())->process($request, new FixedResponseHandler());

        self::assertSame('/dashboard', $response->header('Location'));
    }
}
