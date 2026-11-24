<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Domain\Enum\Role;
use App\Http\Exception\ForbiddenException;
use App\Security\Ability;
use App\Security\Authorization;
use PHPUnit\Framework\TestCase;
use Tests\Fake\TestUsers;

final class AuthorizationTest extends TestCase
{
    private Authorization $authorization;

    protected function setUp(): void
    {
        $this->authorization = new Authorization();
    }

    public function testAdminHasAllAbilities(): void
    {
        $admin = TestUsers::make(role: Role::Admin);

        foreach (Ability::cases() as $ability) {
            self::assertTrue($this->authorization->can($admin, $ability), $ability->name);
        }
    }

    public function testOperatorHasNoAdminAbilities(): void
    {
        $operator = TestUsers::make(role: Role::Operator);

        foreach (Ability::cases() as $ability) {
            self::assertFalse($this->authorization->can($operator, $ability), $ability->name);
        }
    }

    public function testInactiveAdminLosesAbilities(): void
    {
        self::assertFalse($this->authorization->can(TestUsers::make(role: Role::Admin, isActive: false), Ability::ManageUsers));
    }

    public function testAuthorizeThrowsForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->authorization->authorize(TestUsers::make(role: Role::Operator), Ability::ManageFleet);
    }
}
