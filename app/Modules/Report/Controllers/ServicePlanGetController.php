<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\ServicePlanSummaryService;
use App\Modules\Sale\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServicePlanGetController extends Controller
{
    public function __construct(
        private readonly ServicePlanSummaryService $summaryService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('reports.service_plan') ?? false, 403);

        $dateFrom = $request->date('date_from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date('date_to')?->toDateString() ?? now()->toDateString();

        $groupBy = in_array($request->string('group_by')->toString(), [ServicePlanSummaryService::GROUP_SERVICE, ServicePlanSummaryService::GROUP_PLAN], true)
            ? $request->string('group_by')->toString()
            : ServicePlanSummaryService::GROUP_SERVICE;

        $status = in_array($request->string('status')->toString(), Sale::STATUSES, true)
            ? $request->string('status')->toString()
            : null;

        $capacity = in_array($request->string('capacity')->toString(), Sale::CAPACITIES, true)
            ? $request->string('capacity')->toString()
            : null;

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'group_by' => $groupBy,
            'service_id' => $request->filled('service_id') ? $request->string('service_id')->toString() : null,
            'status' => $status,
            'capacity' => $capacity,
            'limit' => $request->integer('limit', 50),
            'offset' => $request->integer('offset', 0),
        ];

        $companyId = (string) session('current_company_id');

        $searched = $request->boolean('searched');

        $result = $searched
            ? $this->summaryService->execute($filters, $companyId)
            : [
                'data' => [],
                'summary' => [
                    'total_sales' => 0,
                    'total_revenue' => 0.0,
                    'avg_ticket' => 0.0,
                    'profile_count' => 0,
                    'full_account_count' => 0,
                    'top_label' => null,
                ],
                'total' => 0,
            ];

        return Inertia::render('reports/service-plan/index', [
            'searched' => $searched,
            'rows' => $result['data'],
            'summary' => $result['summary'],
            'meta' => [
                'total' => $result['total'],
                'limit' => $filters['limit'],
                'offset' => $filters['offset'],
                'has_more' => $result['total'] > $filters['offset'] + $filters['limit'],
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'group_by' => $groupBy,
                'service_id' => $filters['service_id'],
                'status' => $status,
                'capacity' => $capacity,
                'limit' => $filters['limit'],
                'offset' => $filters['offset'],
            ],
        ]);
    }
}
