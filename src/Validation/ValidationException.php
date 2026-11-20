<?php

declare(strict_types=1);

namespace App\Validation;

use App\Http\Exception\HttpException;

final class ValidationException extends HttpException
{
    /**
     * @param array<string, string> $fieldErrors
     */
    public function __construct(private readonly array $fieldErrors)
    {
        parent::__construct(422, 'error.validation');
    }

    public function context(): array
    {
        return ['fields' => $this->fieldErrors];
    }
}
