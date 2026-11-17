<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\IncomeExpenseReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeExpenseReportGetController extends Controller
{
    public function __construct(
        private readonly IncomeExpenseReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('reports.income-expense') ?? false, 403);

        $companyId = (string) ($request->route('company') ?? $request->session()->get('current_company_id'));

        $data = $this->reportService->execute($companyId, $request->all());

        return Inertia::render('reports/income-expense', $data);
    }
}
