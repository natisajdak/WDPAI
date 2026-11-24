<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\User;
use App\Repository\Contract\UserRepositoryInterface;

/**
 * Operacje na kontach użytkowników. W etapie 9 dojdzie tworzenie kont i dezaktywacja.
 */
final class UserService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * @return list<User>
     */
    public function all(): array
    {
        return $this->users->findAll();
    }

    public function changeLocale(User $user, string $locale): void
    {
        if ($user->locale !== $locale) {
            $this->users->updateLocale($user->id, $locale);
        }
    }
}
