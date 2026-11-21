<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\I18n\Translator;
use App\Validation\Rule\DateFormat;
use App\Validation\Rule\Email;
use App\Validation\Rule\InList;
use App\Validation\Rule\IntRange;
use App\Validation\Rule\Length;
use App\Validation\Rule\Required;
use App\Validation\ValidationException;
use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private Translator $translator;

    private Validator $validator;

    protected function setUp(): void
    {
        $this->translator = new Translator(dirname(__DIR__, 3) . '/lang', 'pl', ['pl', 'en']);
        $this->validator = new Validator($this->translator);
    }

    public function testValidDataPassesAndIsTrimmed(): void
    {
        $result = $this->validator->validate(
            ['email' => '  operator@cargo.local ', 'title' => 'Wyciek oleju'],
            ['email' => [new Required(), new Email()], 'title' => [new Required(), new Length(3, 150)]],
        );

        self::assertTrue($result->isValid());
        self::assertSame(['email' => 'operator@cargo.local', 'title' => 'Wyciek oleju'], $result->validated());
    }

    public function testReportsFirstErrorPerFieldInCurrentLanguage(): void
    {
        $rules = ['email' => [new Required(), new Email()], 'title' => [new Required(), new Length(min: 3)]];

        $result = $this->validator->validate(['email' => '', 'title' => 'ab'], $rules);

        self::assertFalse($result->isValid());
        self::assertSame('To pole jest wymagane.', $result->error('email'));
        self::assertSame('Minimalna liczba znaków: 3.', $result->error('title'));

        $this->translator->setLocale('en');
        $english = $this->validator->validate(['email' => 'nie-mail'], ['email' => [new Required(), new Email()]]);

        self::assertSame('Enter a valid email address.', $english->error('email'));
    }

    public function testOptionalEmptyFieldSkipsOtherRules(): void
    {
        $result = $this->validator->validate(['note' => '   '], ['note' => [new Length(max: 10)]]);

        self::assertTrue($result->isValid());
        self::assertSame(['note' => null], $result->validated());
    }

    public function testLengthCountsCharactersNotBytes(): void
    {
        $result = $this->validator->validate(['word' => 'żółć'], ['word' => [new Length(max: 4)]]);

        self::assertTrue($result->isValid());
    }

    public function testIntRange(): void
    {
        $rules = ['year' => [new Required(), new IntRange(1950, 2026)]];

        self::assertTrue($this->validator->validate(['year' => '2021'], $rules)->isValid());
        self::assertSame('Podaj liczbę od 1950 do 2026.', $this->validator->validate(['year' => '2030'], $rules)->error('year'));
        self::assertSame('Podaj liczbę całkowitą.', $this->validator->validate(['year' => '20.5'], $rules)->error('year'));
    }

    public function testDateFormatRejectsImpossibleDate(): void
    {
        $rules = ['date' => [new Required(), new DateFormat(DateFormat::DATE)]];

        self::assertTrue($this->validator->validate(['date' => '2026-10-20'], $rules)->isValid());
        self::assertFalse($this->validator->validate(['date' => '2026-02-31'], $rules)->isValid());
        self::assertFalse($this->validator->validate(['date' => '20.10.2026'], $rules)->isValid());
    }

    public function testInList(): void
    {
        $rules = ['severity' => [new Required(), new InList(['LOW', 'MEDIUM', 'CRITICAL'])]];

        self::assertTrue($this->validator->validate(['severity' => 'LOW'], $rules)->isValid());
        self::assertFalse($this->validator->validate(['severity' => 'low'], $rules)->isValid());
        self::assertFalse($this->validator->validate(['severity' => ['LOW']], $rules)->isValid());
    }

    public function testThrowIfInvalidCarriesFieldErrors(): void
    {
        $result = $this->validator->validate([], ['email' => [new Required()]]);

        try {
            $result->throwIfInvalid();
            self::fail('Oczekiwano ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(422, $e->status());
            self::assertSame(['fields' => ['email' => 'To pole jest wymagane.']], $e->context());
        }
    }
}
