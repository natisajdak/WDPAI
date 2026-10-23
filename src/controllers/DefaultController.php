<?php

declare(strict_types=1);

namespace App\Controller;

final class DefaultController extends AppController
{
    // --- STARE METODY (zostawiamy jako backup) ---
    public function index(): void {
        $this->render('home', ['pageTitle' => 'Moja aplikacja']);
    }

    public function notFound(): void {
        $this->render('not-found', ['pageTitle' => 'Nie znaleziono strony']);
    }

    // --- NOWE METODY (Projekt Widlak) ---
    public function login(): void {
        $this->render('login');
    }

    public function dashboard(): void {
        $this->render('operator-dashboard');
    }

    public function forklifts(): void {
        $this->render('forklift-index');
    }

    public function forkliftShow(int $id): void {
        $this->render('forklift-show');
    }

    public function faultCreate(): void {
        $this->render('fault-create');
    }
}