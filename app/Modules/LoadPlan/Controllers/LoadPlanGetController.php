<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LoadPlan\Commands\SearchLoadPlanCommand;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Resources\LoadPlanResource;
use App\Modules\LoadPlan\Services\LoadPlanFindService;
use App\Modules\LoadPlan\Services\LoadPlanFormOptionsService;
use App\Modules\LoadPlan\Services\LoadPlanPendingDispatchesService;
use App\Modules\LoadPlan\Services\LoadPlanSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoadPlanGetController extends Controller
{
    public function __construct(
        private readonly LoadPlanSearchService $searchService,
        private readonly LoadPlanFindService $findService,
        private readonly LoadPlanFormOptionsService $formOptionsService,
        private readonly LoadPlanPendingDispatchesService $pendingDispatchesService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('load-plans.list') ?? false, 403);

        $command = new SearchLoadPlanCommand(
            filters: $request->only(['q', 'code', 'route_id', 'warehouse_id', 'vehicle_id', 'status', 'date_from', 'date_to']),
            limit: $request->integer('limit', 10),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('load-plans/index', [
            'loadPlans' => array_map(
                fn (LoadPlan $plan): array => (new LoadPlanResource($plan))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['q', 'code', 'route_id', 'warehouse_id', 'vehicle_id', 'status', 'date_from', 'date_to', 'limit', 'offset']),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('load-plans.create') ?? false, 403);

        return Inertia::render('load-plans/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('load-plans.show') ?? false, 403);

        $loadPlan = $this->findService->execute($id, $company);

        return Inertia::render('load-plans/show', [
            'loadPlan' => (new LoadPlanResource($loadPlan))->resolve(),
        ]);
    }

    public function edit(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('load-plans.update') ?? false, 403);

        $loadPlan = $this->findService->execute($id, $company);

        return Inertia::render('load-plans/edit', [
            'loadPlan' => (new LoadPlanResource($loadPlan))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }

    public function pendingDispatches(Request $request, string $company): JsonResponse
    {
        $hasPermission = ($request->user()?->hasPermission('load-plans.create') ?? false)
            || ($request->user()?->hasPermission('load-plans.update') ?? false);

        abort_unless($hasPermission, 403);

        $routeId = $request->query('route_id');
        $excludePlanId = $request->query('exclude_plan_id');

        $dispatches = $this->pendingDispatchesService->execute(
            companyId: $company,
            routeId: $routeId ? (string) $routeId : null,
            excludePlanId: $excludePlanId ? (string) $excludePlanId : null,
        );

        return response()->json([
            'data' => $dispatches,
        ]);
    }
}
