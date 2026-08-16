<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientType\Commands\SearchClientTypeCommand;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Resources\ClientTypeResource;
use App\Modules\ClientType\Services\ClientTypeFindService;
use App\Modules\ClientType\Services\ClientTypeSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientTypeGetController extends Controller
{
    public function __construct(
        private readonly ClientTypeSearchService $searchService,
        private readonly ClientTypeFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('client-types.list') ?? false, 403);

        $command = new SearchClientTypeCommand(
            filters: $request->only(['name', 'code', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('client-types/index', [
            'clientTypes' => array_map(
                fn (ClientType $clientType): array => (new ClientTypeResource($clientType))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'code', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('client-types.create') ?? false, 403);

        return Inertia::render('client-types/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('client-types.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('client-types/show', [
            'clientType' => (new ClientTypeResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('client-types.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('client-types/edit', [
            'clientType' => (new ClientTypeResource($model))->resolve(),
        ]);
    }
}
