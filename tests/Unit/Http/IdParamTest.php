<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Exception\BadRequestException;
use App\Http\IdParam;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdParamTest extends TestCase
{
    public function testParsesPositiveInteger(): void
    {
        self::assertSame(42, IdParam::parse('42'));
        self::assertSame(PHP_INT_MAX, IdParam::parse((string) PHP_INT_MAX));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'zero' => ['0'];
        yield 'ujemna' => ['-1'];
        yield 'litery' => ['abc'];
        yield 'liczba z literą' => ['12a'];
        yield 'zero wiodące' => ['007'];
        yield 'plus' => ['+5'];
        yield 'ułamek' => ['1.5'];
        yield 'pusty' => [''];
        yield 'poza zakresem bigint' => ['9223372036854775808'];
    }

    #[DataProvider('invalidIds')]
    public function testRejectsInvalidIdWithBadRequest(string $raw): void
    {
        try {
            IdParam::parse($raw);
            self::fail('Oczekiwano BadRequestException.');
        } catch (BadRequestException $e) {
            self::assertSame(400, $e->status());
            self::assertSame('error.invalid_id', $e->messageKey());
        }
    }
}
