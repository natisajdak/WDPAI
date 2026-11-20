<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use App\Validation\Violation;
use InvalidArgumentException;

final class Length implements Rule
{
    public function __construct(
        private readonly ?int $min = null,
        private readonly ?int $max = null,
    ) {
        if ($min === null && $max === null) {
            throw new InvalidArgumentException('Reguła Length wymaga min albo max.');
        }
    }

    public function check(mixed $value): ?Violation
    {
        if (!is_string($value)) {
            return new Violation('validation.string');
        }

        // mb_strlen liczy znaki, a nie bajty, więc „żółć” ma długość 4.
        $length = mb_strlen($value);

        if ($this->min !== null && $length < $this->min) {
            return new Violation('validation.length_min', ['min' => $this->min]);
        }

        if ($this->max !== null && $length > $this->max) {
            return new Violation('validation.length_max', ['max' => $this->max]);
        }

        return null;
    }
}
