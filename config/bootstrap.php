<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;

// Wspólny start aplikacji dla public/index.php i (w przyszłości) bin/console.php.

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

// Ostrzeżenia i notice zamieniamy na wyjątki: w poprawnym scenariuszu aplikacja nie może ich generować.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

$config = new Config((require $root . '/config/app.php') + [
    'paths' => [
        'root' => $root,
        'config' => $root . '/config',
        'lang' => $root . '/lang',
        'public' => $root . '/public',
        'templates' => $root . '/templates',
    ],
]);

date_default_timezone_set($config->string('app.timezone'));

$container = new Container();
$container->set(Config::class, static fn (): Config => $config);
$container->set(Container::class, static fn (Container $c): Container => $c);
(require $root . '/config/services.php')($container, $config);

return $container;
