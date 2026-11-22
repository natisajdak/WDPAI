<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Wartości odpowiadają roles.code w bazie.
 */
enum Role: string
{
    case Operator = 'OPERATOR';
    case Admin = 'ADMIN';

    public function labelKey(): string
    {
        return 'role.' . strtolower($this->value);
    }
}
