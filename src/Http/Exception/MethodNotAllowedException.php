<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class MethodNotAllowedException extends HttpException
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(private readonly array $allowedMethods)
    {
        parent::__construct(405);
    }

    /**
     * @return list<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }

    public function headers(): array
    {
        return ['Allow' => implode(', ', $this->allowedMethods)];
    }
}
