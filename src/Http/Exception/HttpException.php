<?php

declare(strict_types=1);

namespace App\Http\Exception;

use RuntimeException;
use Throwable;

/**
 * Błąd, który ma zakończyć się kontrolowaną odpowiedzią HTTP o danym statusie.
 * Treść komunikatu to klucz tłumaczenia, więc działa w obu językach.
 */
class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $status,
        private readonly ?string $messageKey = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($messageKey ?? sprintf('HTTP %d', $status), $status, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function messageKey(): string
    {
        return $this->messageKey ?? sprintf('error.%d.message', $this->status);
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [];
    }

    /**
     * Dodatkowe dane dołączane do odpowiedzi JSON, np. błędy poszczególnych pól formularza.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
