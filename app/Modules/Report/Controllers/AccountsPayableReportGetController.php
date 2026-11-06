<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\AccountsPayableReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountsPayableReportGetController extends Controller
{
    public function __construct(
        private readonly AccountsPayableReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('reports.accounts-payable') ?? false, 403);

        $companyId = (string) ($request->route('company') ?? $request->session()->get('current_company_id'));

        $data = $this->reportService->execute($companyId, $request->all());

        return Inertia::render('reports/accounts-payable', $data);
    }
}
