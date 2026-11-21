<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\Database;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;
use PDOException;

/**
 * Sprawdzenie, czy aplikacja działa i ma połączenie z bazą (do monitoringu i healthchecku wdrożenia).
 */
final class HealthController
{
    public function __construct(private readonly Database $database)
    {
    }

    public function show(Request $request): Response
    {
        try {
            $this->database->fetchValue('SELECT 1');
        } catch (PDOException $e) {
            error_log('Health check: ' . $e->getMessage());

            return new JsonResponse(['status' => 'error', 'database' => 'unavailable'], 503);
        }

        return new JsonResponse(['status' => 'ok', 'database' => 'ok']);
    }
}
