<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Entity\User;
use App\Domain\Enum\Role;
use App\Http\Exception\ForbiddenException;
use App\Http\Exception\UnauthorizedException;
use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;
use LogicException;

/**
 * Dostęp tylko dla wskazanych ról, np. RoleMiddleware::class . ':ADMIN'. Inna rola dostaje 403.
 */
final class RoleMiddleware implements ConfigurableMiddlewareInterface
{
    /** @var list<Role> */
    private array $roles = [];

    public function withArguments(string ...$arguments): static
    {
        $clone = clone $this;
        $clone->roles = array_values(array_map(static fn (string $code): Role => Role::from($code), $arguments));

        return $clone;
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($this->roles === []) {
            throw new LogicException('RoleMiddleware wymaga listy ról, np. RoleMiddleware::class . ":ADMIN".');
        }

        $user = $request->attribute(CurrentUserMiddleware::ATTRIBUTE);

        if (!$user instanceof User) {
            throw new UnauthorizedException();
        }

        if (!in_array($user->role, $this->roles, true)) {
            throw new ForbiddenException();
        }

        return $next->handle($request);
    }
}
