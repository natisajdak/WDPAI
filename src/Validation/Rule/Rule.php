<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use App\Validation\Violation;

interface Rule
{
    /**
     * @return Violation|null null, gdy wartość jest poprawna
     */
    public function check(mixed $value): ?Violation;
}
