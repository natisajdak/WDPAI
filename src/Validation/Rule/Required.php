<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use App\Validation\Violation;

final class Required implements Rule
{
    public function check(mixed $value): ?Violation
    {
        return $value === null || $value === '' || $value === [] ? new Violation('validation.required') : null;
    }
}
