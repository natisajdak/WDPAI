<?php

declare(strict_types=1);

use App\Http\Request;
use App\Kernel;

/** @var \App\Core\Container $container */
$container = require dirname(__DIR__) . '/config/bootstrap.php';

$container->get(Kernel::class)->handle(Request::fromGlobals())->send();
