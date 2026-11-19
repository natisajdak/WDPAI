<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class UnauthorizedException extends HttpException
{
    public function __construct(?string $messageKey = null)
    {
        parent::__construct(401, $messageKey);
    }
}
