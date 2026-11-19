<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\BadRequestException;

/**
 * Walidacja identyfikatora z adresu (np. /forklifts/{id}).
 * Akceptuje wyłącznie dodatnie liczby całkowite mieszczące się w bigint, bez znaków i zer wiodących.
 */
final class IdParam
{
    public static function parse(string $raw): int
    {
        if (!ctype_digit($raw)) {
            throw new BadRequestException('error.invalid_id');
        }

        // FILTER_VALIDATE_INT odrzuca też zera wiodące i wartości większe niż PHP_INT_MAX (= max bigint).
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new BadRequestException('error.invalid_id');
        }

        return $id;
    }
}
