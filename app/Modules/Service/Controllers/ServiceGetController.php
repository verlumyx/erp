<?php

declare(strict_types=1);

namespace App\Modules\Service\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Commands\SearchServiceCommand;
use App\Modules\Service\Services\ServiceFindService;
use App\Modules\Service\Services\ServiceSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceGetController extends Controller
{
    public function __construct(
        private readonly ServiceSearchService $searchService,
        private readonly ServiceFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('services.list') ?? false, 403);

        $command = new SearchServiceCommand(
            filters: $request->only(['name', 'code', 'active']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('services/index', [
            'services' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'code', 'active', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('services.create') ?? false, 403);

        return Inertia::render('services/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('services.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('services/show', [
            'service' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('services.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('services/edit', [
            'service' => $model,
        ]);
    }
}
