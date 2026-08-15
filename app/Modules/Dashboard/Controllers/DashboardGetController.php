<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardExpirationsService;
use App\Modules\Dashboard\Services\DashboardMetricsService;
use App\Modules\Dashboard\Services\DashboardOccupancyService;
use App\Modules\Dashboard\Services\DashboardPlatformsService;
use App\Modules\Dashboard\Services\DashboardRevenueService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardGetController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsService $metrics,
        private readonly DashboardRevenueService $revenue,
        private readonly DashboardOccupancyService $occupancy,
        private readonly DashboardPlatformsService $platforms,
        private readonly DashboardExpirationsService $expirations,
    ) {}

    public function index(Request $request): Response
    {
        $companyId = (string) session('current_company_id');

        return Inertia::render('dashboard', [
            'metrics' => Inertia::defer(fn (): array => $this->metrics->forCompany($companyId), 'metrics'),
            'revenue' => Inertia::defer(fn (): array => $this->revenue->lastSixMonths($companyId), 'revenue'),
            'occupancy' => Inertia::defer(fn (): array => $this->occupancy->forCompany($companyId), 'platforms'),
            'platforms' => Inertia::defer(fn (): array => $this->platforms->forCompany($companyId), 'platforms'),
            'expirations' => Inertia::defer(fn (): array => $this->expirations->upcoming($companyId), 'expirations'),
        ]);
    }
}
