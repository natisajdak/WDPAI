<?php

declare(strict_types=1);

namespace App\Security;

use App\Domain\Entity\User;
use App\Domain\Enum\Role;
use App\Http\Exception\ForbiddenException;

/**
 * Jedno miejsce z regułami uprawnień. Backend sprawdza je przed każdą chronioną operacją;
 * ukrycie przycisku w widoku jest tylko wygodą dla użytkownika.
 */
final class Authorization
{
    public function can(User $user, Ability $ability): bool
    {
        if (!$user->isActive) {
            return false;
        }

        return match ($ability) {
            Ability::ManageUsers,
            Ability::ManageFleet,
            Ability::HandleFaults,
            Ability::ViewReports,
            Ability::ViewAllReservations => $user->hasRole(Role::Admin),
        };
    }

    public function authorize(User $user, Ability $ability): void
    {
        if (!$this->can($user, $ability)) {
            throw new ForbiddenException();
        }
    }
}
