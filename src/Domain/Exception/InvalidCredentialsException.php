<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Celowo bez szczegółów: zły e-mail, złe hasło i konto nieaktywne dają ten sam komunikat.
 */
final class InvalidCredentialsException extends RuntimeException
{
}
