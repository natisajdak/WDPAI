<?php

declare(strict_types=1);

// Tymczasowy punkt wejścia do sprawdzenia infrastruktury (etap 1).
// W etapie 3 zastąpi go Kernel z routerem, a sprawdzenie bazy przeniesie się do osobnej trasy.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$status = ['php' => PHP_VERSION, 'database' => 'unavailable'];

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_NAME'));
    $pdo = new PDO($dsn, (string) getenv('DB_USER'), (string) getenv('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $status['database'] = (string) $pdo->query('SELECT version()')->fetchColumn();
    http_response_code(200);
} catch (PDOException $e) {
    error_log('Health check: ' . $e->getMessage());
    http_response_code(503);
}

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
