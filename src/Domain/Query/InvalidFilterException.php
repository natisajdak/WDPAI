<?php

declare(strict_types=1);

namespace App\Domain\Query;

use InvalidArgumentException;

final class InvalidFilterException extends InvalidArgumentException
{
    public function __construct(public readonly string $parameter)
    {
        parent::__construct(sprintf('Niepoprawna wartość parametru "%s".', $parameter));
    }
}
