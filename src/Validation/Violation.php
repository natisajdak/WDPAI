<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Naruszenie reguły jako klucz tłumaczenia z parametrami, np. validation.length_max + ['max' => 150].
 */
final class Violation
{
    /**
     * @param array<string, string|int|float> $params
     */
    public function __construct(
        public readonly string $messageKey,
        public readonly array $params = [],
    ) {
    }
}
