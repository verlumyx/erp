<?php

declare(strict_types=1);

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Resources\ClientResource;
use App\Modules\Client\Services\ClientFindService;
use App\Modules\Client\Services\ClientFormOptionsService;
use App\Modules\Client\Services\ClientSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientGetController extends Controller
{
    public function __construct(
        private readonly ClientSearchService $searchService,
        private readonly ClientFindService $findService,
        private readonly ClientFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('clients.list') ?? false, 403);

        $command = new SearchClientCommand(
            filters: $request->only([
                'name', 'email', 'phone', 'code', 'document_number', 'document_type',
                'client_type_id', 'salesperson_id', 'credit_blocked', 'status',
            ]),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('clients/index', [
            'clients' => array_map(
                fn (Client $client): array => (new ClientResource($client))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([
                'name', 'email', 'phone', 'code', 'document_number', 'document_type',
                'client_type_id', 'salesperson_id', 'credit_blocked', 'status', 'limit', 'offset',
            ]),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('clients.create') ?? false, 403);

        return Inertia::render('clients/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('clients.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('clients/show', [
            'client' => (new ClientResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('clients.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('clients/edit', [
            'client' => (new ClientResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
