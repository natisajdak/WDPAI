<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use App\Validation\Violation;

final class Email implements Rule
{
    private const MAX_LENGTH = 254;

    public function check(mixed $value): ?Violation
    {
        $valid = is_string($value)
            && strlen($value) <= self::MAX_LENGTH
            && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;

        return $valid ? null : new Violation('validation.email');
    }
}
