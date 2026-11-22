<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

final class TooManyLoginAttemptsException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterMinutes)
    {
        parent::__construct(sprintf('Zbyt wiele nieudanych prób logowania. Spróbuj za %d min.', $retryAfterMinutes));
    }
}
