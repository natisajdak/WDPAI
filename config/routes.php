<?php

declare(strict_types=1);

use App\Controller\Admin\UserAdminController;
use App\Controller\Api\MeController;
use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\LocaleController;
use App\Controller\PasswordResetController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Router\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/health', [HealthController::class, 'show']);
    $router->get('/lang/{locale:pl|en}', [LocaleController::class, 'switch']);

    // Tylko dla niezalogowanych.
    $router->group('', [GuestMiddleware::class], static function (Router $guest): void {
        $guest->get('/login', [AuthController::class, 'showLogin']);
        $guest->post('/login', [AuthController::class, 'login']);
        $guest->get('/forgot-password', [PasswordResetController::class, 'showRequestForm']);
        $guest->post('/forgot-password', [PasswordResetController::class, 'sendLink']);
        $guest->get('/reset-password', [PasswordResetController::class, 'showResetForm']);
        $guest->post('/reset-password', [PasswordResetController::class, 'reset']);
    });

    // Tylko dla zalogowanych (każda rola).
    $router->group('', [AuthMiddleware::class], static function (Router $auth): void {
        $auth->post('/logout', [AuthController::class, 'logout']);
        $auth->get('/dashboard', [DashboardController::class, 'index']);
        $auth->get('/api/me', [MeController::class, 'show']);

        // Panel kierownika. Rola jest sprawdzana w backendzie, nie tylko przez ukrycie linków.
        $auth->group('/admin', [RoleMiddleware::class . ':ADMIN'], static function (Router $admin): void {
            $admin->get('/users', [UserAdminController::class, 'index']);
        });
    });
};
