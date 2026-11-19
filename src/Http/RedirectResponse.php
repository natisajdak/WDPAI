<?php

declare(strict_types=1);

namespace App\Http;

final class RedirectResponse extends Response
{
    /**
     * Status 303 po POST sprawia, że przeglądarka pobiera nową stronę metodą GET,
     * więc odświeżenie nie wyśle formularza ponownie.
     */
    public function __construct(string $location, int $status = 303)
    {
        parent::__construct('', $status, ['Location' => $location]);
    }
}
