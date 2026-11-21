<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;
use App\Database\Database;
use App\Http\ErrorResponder;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\ErrorMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\MiddlewareFactory;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SessionMiddleware;
use App\Http\RouteDispatcher;
use App\Http\Router\Router;
use App\I18n\Translator;
use App\Kernel;
use App\Security\ContentSecurityPolicy;
use App\Security\Csrf;
use App\Security\NativeSession;
use App\Security\SessionInterface;
use App\View\View;

// Usługi wymagające konfiguracji albo wiązania interfejsu z implementacją.
// Wszystkie pozostałe klasy kontener tworzy automatycznie na podstawie konstruktorów.
return static function (Container $container, Config $config): void {
    $container->set(Database::class, static fn (): Database => new Database(
        sprintf('pgsql:host=%s;port=%d;dbname=%s', $config->string('db.host'), $config->int('db.port'), $config->string('db.name')),
        $config->string('db.user'),
        $config->string('db.password'),
        $config->string('app.timezone'),
    ));

    $container->set(SessionInterface::class, static fn (): SessionInterface => new NativeSession(
        $config->string('session.name'),
        $config->bool('session.secure'),
        $config->int('session.lifetime'),
    ));

    $container->set(Translator::class, static fn (): Translator => new Translator(
        $config->string('paths.lang'),
        $config->string('app.default_locale'),
        $config->get('app.locales'),
    ));

    $container->set(View::class, static fn (Container $c): View => new View(
        $config->string('paths.templates'),
        $config->string('paths.public'),
        $c->get(Translator::class),
        $c->get(Csrf::class),
        $c->get(ContentSecurityPolicy::class),
    ));

    $container->set(ErrorResponder::class, static fn (Container $c): ErrorResponder => new ErrorResponder(
        $c->get(View::class),
        $c->get(Translator::class),
        $config->bool('app.debug'),
    ));

    $container->set(Router::class, static function () use ($config): Router {
        $router = new Router();
        (require $config->string('paths.config') . '/routes.php')($router);

        return $router;
    });

    $container->set(Kernel::class, static fn (Container $c): Kernel => new Kernel(
        $c->get(MiddlewareFactory::class),
        $c->get(RouteDispatcher::class),
        [
            // Od zewnątrz: nagłówki bezpieczeństwa trafiają też na strony błędów,
            // a ErrorMiddleware łapie wyjątki z sesji, języka, CSRF i kontrolerów.
            SecurityHeadersMiddleware::class,
            ErrorMiddleware::class,
            SessionMiddleware::class,
            LocaleMiddleware::class,
            CsrfMiddleware::class,
        ],
    ));
};
