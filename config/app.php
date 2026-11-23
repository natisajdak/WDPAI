<?php

declare(strict_types=1);

use App\Core\Env;

// Jedyne miejsce, w którym aplikacja czyta zmienne środowiskowe (opis w .env.example).
return [
    'app' => [
        'env' => Env::string('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG'),
        'url' => Env::string('APP_URL'),
        'timezone' => 'Europe/Warsaw',
        'default_locale' => Env::string('APP_DEFAULT_LOCALE', 'pl'),
        'locales' => ['pl', 'en'],
    ],
    'db' => [
        'host' => Env::string('DB_HOST'),
        'port' => Env::int('DB_PORT', 5432),
        'name' => Env::string('DB_NAME'),
        'user' => Env::string('DB_USER'),
        'password' => Env::string('DB_PASSWORD'),
    ],
    'session' => [
        'name' => Env::string('SESSION_NAME', 'cargo_sid'),
        'secure' => Env::bool('SESSION_SECURE'),
        'lifetime' => Env::int('SESSION_LIFETIME', 7200),
    ],
    'mail' => [
        'host' => Env::string('MAIL_HOST'),
        'port' => Env::int('MAIL_PORT', 587),
        'username' => Env::string('MAIL_USERNAME', ''),
        'password' => Env::string('MAIL_PASSWORD', ''),
        'encryption' => Env::string('MAIL_ENCRYPTION', ''),
        'from_address' => Env::string('MAIL_FROM_ADDRESS'),
        'from_name' => Env::string('MAIL_FROM_NAME', 'Cargo'),
    ],
];
