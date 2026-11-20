<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use App\Validation\Violation;

/**
 * Liczba całkowita (także jako tekst z formularza) w podanym zakresie.
 */
final class IntRange implements Rule
{
    public function __construct(
        private readonly int $min,
        private readonly int $max,
    ) {
    }

    public function check(mixed $value): ?Violation
    {
        $int = is_int($value) ? $value : filter_var($value, FILTER_VALIDATE_INT);

        if ($int === false) {
            return new Violation('validation.integer');
        }

        if ($int < $this->min || $int > $this->max) {
            return new Violation('validation.int_range', ['min' => $this->min, 'max' => $this->max]);
        }

        return null;
    }
}
