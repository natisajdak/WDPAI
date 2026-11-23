<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Token nie istnieje, wygasł albo został już użyty.
 */
final class InvalidPasswordResetTokenException extends RuntimeException
{
}
