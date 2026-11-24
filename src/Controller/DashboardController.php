<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;

/**
 * Pulpit po zalogowaniu. W etapie 5 dojdą dane floty (wózki dostępne teraz, usterki, terminy UDT).
 */
final class DashboardController extends AbstractController
{
    public function index(Request $request): Response
    {
        return $this->render('dashboard/index', [
            'titleKey' => 'dashboard.title',
            'user' => $this->user($request),
        ]);
    }
}
