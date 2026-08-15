<?php

declare(strict_types=1);

namespace App\Modules\Plan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plan\Commands\SearchPlanCommand;
use App\Modules\Plan\Services\PlanFindService;
use App\Modules\Plan\Services\PlanSearchService;
use App\Modules\Service\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanGetController extends Controller
{
    public function __construct(
        private readonly PlanSearchService $searchService,
        private readonly PlanFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('plans.list') ?? false, 403);

        $command = new SearchPlanCommand(
            filters: $request->only(['name', 'code', 'capacity', 'service_id', 'active']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('plans/index', [
            'plans' => $result['data'],
            'services' => $this->activeServices(session('current_company_id')),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'code', 'capacity', 'service_id', 'active', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('plans.create') ?? false, 403);

        return Inertia::render('plans/create', [
            'services' => $this->activeServices(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('plans.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('plans/show', [
            'plan' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('plans.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('plans/edit', [
            'plan' => $model,
            'services' => $this->activeServices($company),
        ]);
    }

    /**
     * Active services of the company, used to populate the plan form select.
     *
     * @return array<int, array{id: string, name: string, code: string, max_profiles: int}>
     */
    private function activeServices(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Service::query()
            ->where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'max_profiles'])
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
                'max_profiles' => $service->max_profiles,
            ])
            ->all();
    }
}
