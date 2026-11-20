<?php

declare(strict_types=1);

namespace App\Validation;

final class ValidationResult
{
    /**
     * @param array<string, mixed> $validated wartości pól objętych regułami (przycięte, puste jako null)
     * @param array<string, string> $errors pierwszy błąd każdego pola, już przetłumaczony
     */
    public function __construct(
        private readonly array $validated,
        private readonly array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * Dla API: niepoprawne dane kończą się odpowiedzią 422 z błędami pól.
     */
    public function throwIfInvalid(): void
    {
        if (!$this->isValid()) {
            throw new ValidationException($this->errors);
        }
    }
}
