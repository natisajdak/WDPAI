<?php

declare(strict_types=1);

namespace App\Controller;

final class UserController extends AppController
{
    public function profile(int $id): void
    {
        // Symulacja pobrania danych z bazy
        $this->render('user-profile', [
            'pageTitle' => 'Profil Użytkownika',
            'userId' => $id,
            'userName' => 'Jan Kowalski',
            'userRole' => 'Administrator'
        ]);
    }
}