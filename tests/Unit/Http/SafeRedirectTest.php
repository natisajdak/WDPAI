<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use App\Http\SafeRedirect;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SafeRedirectTest extends TestCase
{
    public function testAcceptsLocalPathWithQuery(): void
    {
        self::assertSame('/forklifts?status=AVAILABLE', SafeRedirect::localPath('/forklifts?status=AVAILABLE'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function externalTargets(): iterable
    {
        yield 'pełny adres' => ['https://evil.example/'];
        yield 'protocol-relative' => ['//evil.example/'];
        yield 'backslash' => ['/\\evil.example/'];
        yield 'bez ukośnika' => ['evil.example'];
        yield 'javascript' => ['javascript:alert(1)'];
    }

    #[DataProvider('externalTargets')]
    public function testRejectsExternalTargets(string $target): void
    {
        self::assertNull(SafeRedirect::localPath($target));
    }

    public function testBackUsesRefererFromSameHost(): void
    {
        $request = new Request('GET', '/lang/en', headers: ['host' => 'localhost:8080', 'referer' => 'http://localhost:8080/forklifts?page=2']);

        self::assertSame('/forklifts?page=2', SafeRedirect::back($request));
    }

    public function testBackIgnoresRefererFromOtherHost(): void
    {
        $request = new Request('GET', '/lang/en', headers: ['host' => 'localhost:8080', 'referer' => 'https://evil.example/phishing']);

        self::assertSame('/', SafeRedirect::back($request));
    }
}
