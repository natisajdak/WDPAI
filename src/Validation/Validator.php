<?php

declare(strict_types=1);

namespace App\Validation;

use App\I18n\Translator;
use App\Validation\Rule\Required;
use App\Validation\Rule\Rule;

/**
 * Jedno miejsce walidacji danych wejściowych. Komunikaty są tłumaczone na bieżący język interfejsu.
 *
 * Teksty są przycinane (trim). Pole bez reguły Required może być puste: wtedy pozostałe reguły są pomijane.
 */
final class Validator
{
    public function __construct(private readonly Translator $translator)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, list<Rule>> $rules
     */
    public function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $validated[$field] = $value === '' ? null : $value;

            $isEmpty = $value === null || $value === '';
            $isRequired = array_any($fieldRules, static fn (Rule $rule): bool => $rule instanceof Required);

            if ($isEmpty && !$isRequired) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                $violation = $rule->check($value);

                if ($violation !== null) {
                    $errors[$field] = $this->translator->trans($violation->messageKey, $violation->params);
                    break;
                }
            }
        }

        return new ValidationResult($validated, $errors);
    }
}
