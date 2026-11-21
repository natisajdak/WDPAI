<?php

declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\LocaleController;
use App\Http\Router\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/health', [HealthController::class, 'show']);
    $router->get('/lang/{locale:pl|en}', [LocaleController::class, 'switch']);
};
