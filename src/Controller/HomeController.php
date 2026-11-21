<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;

/**
 * Strona startowa. W etapie 4 zastąpi ją przekierowanie do logowania albo dashboardu.
 */
final class HomeController extends AbstractController
{
    public function index(Request $request): Response
    {
        return $this->render('home/index');
    }
}
