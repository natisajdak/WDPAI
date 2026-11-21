<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Exception\MethodNotAllowedException;
use App\Http\Exception\NotFoundException;
use App\Http\Router\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/forklifts', ['ForkliftController', 'index']);
        $this->router->get('/forklifts/{id}', ['ForkliftController', 'show']);
        $this->router->post('/reservations/{id}/cancel', ['ReservationController', 'cancel']);
        $this->router->get('/lang/{locale:pl|en}', ['LocaleController', 'switch']);
        $this->router->get('/admin/reports/{year:\d{4}}/{month:0[1-9]|1[0-2]}', ['ReportController', 'monthly']);
    }

    public function testMatchesStaticRoute(): void
    {
        $match = $this->router->match('GET', '/forklifts');

        self::assertSame(['ForkliftController', 'index'], $match->route->handler);
        self::assertSame([], $match->params);
    }

    public function testExtractsPathParameter(): void
    {
        $match = $this->router->match('GET', '/forklifts/42');

        self::assertSame(['ForkliftController', 'show'], $match->route->handler);
        self::assertSame(['id' => '42'], $match->params);
    }

    public function testRegexParameterAcceptsOnlyAllowedValues(): void
    {
        self::assertSame(['locale' => 'en'], $this->router->match('GET', '/lang/en')->params);

        $this->expectException(NotFoundException::class);
        $this->router->match('GET', '/lang/de');
    }

    public function testRegexWithQuantifiersAndAlternation(): void
    {
        $match = $this->router->match('GET', '/admin/reports/2026/09');

        self::assertSame(['year' => '2026', 'month' => '09'], $match->params);
    }

    public function testRejectsInvalidMonth(): void
    {
        $this->expectException(NotFoundException::class);

        $this->router->match('GET', '/admin/reports/2026/13');
    }

    public function testUnknownPathThrowsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->router->match('GET', '/nie-istnieje');
    }

    public function testWrongMethodThrowsMethodNotAllowedWithAllowHeader(): void
    {
        try {
            $this->router->match('GET', '/reservations/5/cancel');
            self::fail('Oczekiwano MethodNotAllowedException.');
        } catch (MethodNotAllowedException $e) {
            self::assertSame(405, $e->status());
            self::assertSame(['Allow' => 'POST'], $e->headers());
        }
    }

    public function testHeadIsHandledByGetRoute(): void
    {
        self::assertSame(['ForkliftController', 'index'], $this->router->match('HEAD', '/forklifts')->route->handler);
    }

    public function testDecodesUrlEncodedParameter(): void
    {
        self::assertSame(['id' => 'a b'], $this->router->match('GET', '/forklifts/a%20b')->params);
    }

    public function testGroupAddsPrefixAndMiddleware(): void
    {
        $router = new Router();
        $router->group('/admin', ['AuthMiddleware'], static function (Router $admin): void {
            $admin->get('/users', ['UserAdminController', 'index'], ['RoleMiddleware:ADMIN']);
        });
        $router->get('/users', ['PublicController', 'index']);

        $adminRoute = $router->match('GET', '/admin/users')->route;

        self::assertSame(['AuthMiddleware', 'RoleMiddleware:ADMIN'], $adminRoute->middleware);
        self::assertSame([], $router->match('GET', '/users')->route->middleware, 'Middleware grupy nie może wyciec poza grupę.');
    }

    public function testRejectsUnclosedParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Router())->get('/forklifts/{id', ['ForkliftController', 'show']);
    }
}
