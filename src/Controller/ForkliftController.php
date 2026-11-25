<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Enum\ForkliftStatus;
use App\Domain\Enum\FuelType;
use App\Domain\Query\ForkliftFilter;
use App\Domain\Query\InvalidFilterException;
use App\Http\Exception\BadRequestException;
use App\Http\Exception\NotFoundException;
use App\Http\Request;
use App\Http\Response;
use App\Service\ForkliftService;
use App\View\View;

final class ForkliftController extends AbstractController
{
    public function __construct(View $view, private readonly ForkliftService $forklifts)
    {
        parent::__construct($view);
    }

    /**
     * GET /forklifts?status=&warehouse=&fuel=&min_load=&q=&sort=&page=
     */
    public function index(Request $request): Response
    {
        try {
            $filter = ForkliftFilter::fromQuery($request->queryParams());
        } catch (InvalidFilterException) {
            throw new BadRequestException('error.invalid_filter');
        }

        return $this->render('forklifts/index', [
            'titleKey' => 'forklifts.title',
            'filter' => $filter,
            'page' => $this->forklifts->search($filter),
            'warehouses' => $this->forklifts->warehouses(),
            'statuses' => ForkliftStatus::cases(),
            'fuelTypes' => FuelType::cases(),
        ]);
    }

    /**
     * GET /forklifts/{id}: niepoprawne id daje 400, nieistniejący wózek 404.
     */
    public function show(Request $request): Response
    {
        $forklift = $this->forklifts->find($request->idParam()) ?? throw new NotFoundException('error.forklift_not_found');

        return $this->render('forklifts/show', [
            'titleKey' => 'forklifts.details_title',
            'forklift' => $forklift,
            'inspections' => $this->forklifts->inspectionHistory($forklift->id),
        ]);
    }
}
