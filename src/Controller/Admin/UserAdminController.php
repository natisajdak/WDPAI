<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Http\Request;
use App\Http\Response;
use App\Service\UserService;
use App\View\View;

/**
 * Lista kont dla kierownika. Dostęp ogranicza RoleMiddleware:ADMIN w config/routes.php.
 */
final class UserAdminController extends AbstractController
{
    public function __construct(View $view, private readonly UserService $users)
    {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        return $this->render('admin/users/index', [
            'titleKey' => 'admin.users.title',
            'users' => $this->users->all(),
        ]);
    }
}
