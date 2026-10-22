<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardGetController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $statsService,
    ) {}

    public function index(Request $request): Response
    {
        $companyId = (string) ($request->route('company') ?? $request->session()->get('current_company_id'));

        $stats = $companyId !== ''
            ? $this->statsService->execute($companyId)
            : $this->statsService->emptyStats();

        return Inertia::render('dashboard', [
            'stats' => $stats,
        ]);
    }
}
