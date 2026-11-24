<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Http\Request;
use App\Http\Response;

/**
 * GET /api/me: dane zalogowanego użytkownika. Bez sesji AuthMiddleware odpowiada 401.
 */
final class MeController extends AbstractController
{
    public function show(Request $request): Response
    {
        $user = $this->user($request);

        return $this->json([
            'id' => $user->id,
            'name' => $user->fullName(),
            'email' => $user->email,
            'role' => $user->role->value,
            'locale' => $user->locale,
        ]);
    }
}
