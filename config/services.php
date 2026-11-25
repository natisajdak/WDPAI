<?php

declare(strict_types=1);

use App\Core\Clock;
use App\Core\Config;
use App\Core\Container;
use App\Core\SystemClock;
use App\Database\Database;
use App\Database\TransactionRunner;
use App\Http\ErrorResponder;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\CurrentUserMiddleware;
use App\Http\Middleware\ErrorMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\MiddlewareFactory;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SessionMiddleware;
use App\Http\RouteDispatcher;
use App\Http\Router\Router;
use App\I18n\Translator;
use App\Kernel;
use App\Mail\MailComposer;
use App\Mail\MailerInterface;
use App\Mail\PhpMailerMailer;
use App\Repository\Contract\ForkliftRepositoryInterface;
use App\Repository\Contract\LoginAttemptRepositoryInterface;
use App\Repository\Contract\PasswordResetTokenRepositoryInterface;
use App\Repository\Contract\TechnicalInspectionRepositoryInterface;
use App\Repository\Contract\UserRepositoryInterface;
use App\Repository\Contract\WarehouseRepositoryInterface;
use App\Repository\Pg\PgForkliftRepository;
use App\Repository\Pg\PgLoginAttemptRepository;
use App\Repository\Pg\PgPasswordResetTokenRepository;
use App\Repository\Pg\PgTechnicalInspectionRepository;
use App\Repository\Pg\PgUserRepository;
use App\Repository\Pg\PgWarehouseRepository;
use App\Security\Authorization;
use App\Security\ContentSecurityPolicy;
use App\Security\Csrf;
use App\Security\NativeSession;
use App\Security\PasswordHasher;
use App\Security\SessionInterface;
use App\Service\PasswordResetService;
use App\View\View;

// Usługi wymagające konfiguracji albo wiązania interfejsu z implementacją.
// Wszystkie pozostałe klasy kontener tworzy automatycznie na podstawie konstruktorów.
return static function (Container $container, Config $config): void {
    // Interfejsy → implementacje
    $container->bind(Clock::class, SystemClock::class);
    $container->bind(TransactionRunner::class, Database::class);
    $container->bind(UserRepositoryInterface::class, PgUserRepository::class);
    $container->bind(LoginAttemptRepositoryInterface::class, PgLoginAttemptRepository::class);
    $container->bind(ForkliftRepositoryInterface::class, PgForkliftRepository::class);
    $container->bind(WarehouseRepositoryInterface::class, PgWarehouseRepository::class);
    $container->bind(TechnicalInspectionRepositoryInterface::class, PgTechnicalInspectionRepository::class);
    $container->bind(PasswordResetTokenRepositoryInterface::class, PgPasswordResetTokenRepository::class);

    // Infrastruktura z konfiguracją ze zmiennych środowiskowych
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

    $container->set(MailerInterface::class, static fn (): MailerInterface => new PhpMailerMailer(
        $config->string('mail.host'),
        $config->int('mail.port'),
        $config->string('mail.username'),
        $config->string('mail.password'),
        $config->string('mail.encryption'),
        $config->string('mail.from_address'),
        $config->string('mail.from_name'),
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
        $c->get(Authorization::class),
        $c->get(SessionInterface::class),
    ));

    // Serwisy z parametrami skalarnymi
    $container->set(PasswordResetService::class, static fn (Container $c): PasswordResetService => new PasswordResetService(
        $c->get(UserRepositoryInterface::class),
        $c->get(PasswordResetTokenRepositoryInterface::class),
        $c->get(PasswordHasher::class),
        $c->get(MailerInterface::class),
        $c->get(MailComposer::class),
        $c->get(TransactionRunner::class),
        $c->get(Clock::class),
        $config->string('app.url'),
    ));

    // HTTP
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
            // a ErrorMiddleware łapie wyjątki z dalszych warstw i kontrolerów.
            SecurityHeadersMiddleware::class,
            ErrorMiddleware::class,
            SessionMiddleware::class,
            CurrentUserMiddleware::class,
            LocaleMiddleware::class,
            CsrfMiddleware::class,
        ],
    ));
};
