<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Uprawnienia sprawdzane przez Authorization. Kontrolery i widoki pytają o uprawnienie, a nie o rolę,
 * więc zmiana podziału ról wymaga zmiany tylko w jednym miejscu.
 */
enum Ability
{
    case ManageUsers;
    case ManageFleet;
    case HandleFaults;
    case ViewReports;
    case ViewAllReservations;
}
