<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use App\Validation\Violation;
use BackedEnum;

final class InList implements Rule
{
    /**
     * @param list<string|int> $allowed
     */
    public function __construct(private readonly array $allowed)
    {
    }

    /**
     * Dozwolone wartości wprost z enuma, np. InList::fromEnum(FaultSeverity::class).
     *
     * @param class-string<BackedEnum> $enumClass
     */
    public static function fromEnum(string $enumClass): self
    {
        return new self(array_map(static fn (BackedEnum $case): string|int => $case->value, $enumClass::cases()));
    }

    public function check(mixed $value): ?Violation
    {
        // Porównanie tekstowe, bo z formularza zawsze przychodzi string.
        $allowed = array_map('strval', $this->allowed);

        return is_scalar($value) && in_array((string) $value, $allowed, true) ? null : new Violation('validation.in_list');
    }
}
