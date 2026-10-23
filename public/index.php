<?php

declare(strict_types=1);

use App\Controller\DefaultController;
use App\Controller\ProjectController;
use App\Controller\UserController;
use App\Routing;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$routing = new Routing();

// --- STARE TRASY (Wyłączone znakiem "//" - są nieaktywne, ale gotowe do przywrócenia) ---
// $routing->get('/projects', ProjectController::class, 'index');
// $routing->get('/projects/{id}', ProjectController::class, 'show');
// $routing->get('/users/{id}', UserController::class, 'profile');
// $routing->get('/', DefaultController::class, 'index');

// --- NOWE TRASY (Aktywne - Projekt Widlak) ---
$routing->get('/login', DefaultController::class, 'login');
$routing->get('/dashboard', DefaultController::class, 'dashboard');
$routing->get('/forklifts', DefaultController::class, 'forklifts');
$routing->get('/forklifts/{id}', DefaultController::class, 'forkliftShow');
$routing->get('/faults/new', DefaultController::class, 'faultCreate');

// Przekierowujemy główny adres bezpośrednio na logowanie
$routing->get('/', DefaultController::class, 'login');

$routeFound = $routing->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
);

if (!$routeFound) {
    http_response_code(404);
    (new DefaultController())->notFound();
}