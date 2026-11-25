<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Security\Ability;
use App\Security\Authorization;
use App\Service\ForkliftService;
use App\View\View;

/**
 * Pulpit po zalogowaniu. Kierownik widzi stan całej floty, operator wózki dostępne od ręki.
 * Rezerwacje operatora dojdą w etapie 6.
 */
final class DashboardController extends AbstractController
{
    public function __construct(
        View $view,
        private readonly ForkliftService $forklifts,
        private readonly Authorization $authorization,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $user = $this->user($request);

        if ($this->authorization->can($user, Ability::ManageFleet)) {
            return $this->render('dashboard/admin', [
                'titleKey' => 'dashboard.title',
                'user' => $user,
                'stats' => $this->forklifts->stats(),
                'udtWarnings' => $this->forklifts->udtWarnings(),
                'outOfService' => $this->forklifts->outOfService(),
                'udtWarningDays' => ForkliftService::UDT_WARNING_DAYS,
            ]);
        }

        return $this->render('dashboard/operator', [
            'titleKey' => 'dashboard.title',
            'user' => $user,
            'bookable' => $this->forklifts->bookableNow(),
        ]);
    }
}
