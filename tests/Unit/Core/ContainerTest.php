<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Container;
use App\Core\ContainerException;
use PHPUnit\Framework\TestCase;

interface Clock
{
}

final class SystemClock implements Clock
{
}

final class ReportService
{
    public function __construct(public readonly Clock $clock)
    {
    }
}

final class NeedsScalar
{
    public function __construct(public readonly string $dsn)
    {
    }
}

final class ContainerTest extends TestCase
{
    public function testAutowiresConstructorDependenciesThroughBinding(): void
    {
        $container = new Container();
        $container->bind(Clock::class, SystemClock::class);

        $service = $container->get(ReportService::class);

        self::assertInstanceOf(SystemClock::class, $service->clock);
    }

    public function testReturnsSameInstanceOnEveryCall(): void
    {
        $container = new Container();

        self::assertSame($container->get(SystemClock::class), $container->get(SystemClock::class));
    }

    public function testFactoryIsUsedForScalarDependencies(): void
    {
        $container = new Container();
        $container->set(NeedsScalar::class, static fn (): NeedsScalar => new NeedsScalar('pgsql:host=db'));

        self::assertSame('pgsql:host=db', $container->get(NeedsScalar::class)->dsn);
    }

    public function testUnboundInterfaceFailsWithClearMessage(): void
    {
        $this->expectException(ContainerException::class);

        (new Container())->get(ReportService::class);
    }

    public function testScalarWithoutFactoryFails(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/\$dsn/');

        (new Container())->get(NeedsScalar::class);
    }
}
