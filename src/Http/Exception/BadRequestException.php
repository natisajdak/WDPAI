<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class BadRequestException extends HttpException
{
    public function __construct(?string $messageKey = null)
    {
        parent::__construct(400, $messageKey);
    }
}
