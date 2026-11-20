<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use App\Validation\Violation;
use DateTimeImmutable;

/**
 * Data w dokładnie podanym formacie, np. Y-m-d (input type="date") albo Y-m-d\TH:i (datetime-local).
 */
final class DateFormat implements Rule
{
    public const DATE = 'Y-m-d';
    public const DATETIME_LOCAL = 'Y-m-d\TH:i';

    public function __construct(private readonly string $format)
    {
    }

    public function check(mixed $value): ?Violation
    {
        if (!is_string($value)) {
            return new Violation('validation.date');
        }

        // "!" zeruje pola spoza formatu, a porównanie po sformatowaniu odrzuca daty typu 2026-02-31.
        $date = DateTimeImmutable::createFromFormat('!' . $this->format, $value);

        return $date !== false && $date->format($this->format) === $value ? null : new Violation('validation.date');
    }
}
