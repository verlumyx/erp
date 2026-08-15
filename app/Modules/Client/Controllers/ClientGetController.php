<?php

declare(strict_types=1);

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Resources\ClientResource;
use App\Modules\Client\Services\ClientFindService;
use App\Modules\Client\Services\ClientSearchService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleRenewal;
use App\Modules\Sale\Resources\SaleResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientGetController extends Controller
{
    public function __construct(
        private readonly ClientSearchService $searchService,
        private readonly ClientFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('clients.list') ?? false, 403);

        $command = new SearchClientCommand(
            filters: $request->only(['name', 'email', 'phone', 'code', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        $companyId = session('current_company_id');
        $clients = $result['data'];
        $platformsByClient = $this->activePlatformsByClient(
            array_map(fn (Client $client): string => $client->id, $clients),
            $companyId,
        );

        return Inertia::render('clients/index', [
            'clients' => array_map(
                fn (Client $client): array => array_merge(
                    (new ClientResource($client))->resolve(),
                    ['platforms' => $platformsByClient[$client->id] ?? []],
                ),
                $clients,
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'email', 'phone', 'code', 'status', 'limit', 'offset']),
        ]);
    }

    /**
     * Plataformas (servicios) que cada cliente tiene activas vía sus ventas activas,
     * indexadas por client_id para el listado.
     *
     * @param  array<int, string>  $clientIds
     * @return array<string, array<int, array{id: string, name: string, code: ?string}>>
     */
    private function activePlatformsByClient(array $clientIds, ?string $companyId): array
    {
        if ($clientIds === [] || $companyId === null) {
            return [];
        }

        return Sale::query()
            ->where('company_id', $companyId)
            ->whereIn('client_id', $clientIds)
            ->where('status', Sale::STATUS_ACTIVE)
            ->with('service:id,name,code')
            ->get(['id', 'client_id', 'service_id'])
            ->groupBy('client_id')
            ->map(fn ($sales): array => $sales
                ->pluck('service')
                ->filter()
                ->unique('id')
                ->map(fn ($service): array => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                ])
                ->values()
                ->all())
            ->all();
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('clients.create') ?? false, 403);

        return Inertia::render('clients/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('clients.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('clients/show', [
            'client' => $model,
            'sales' => $this->clientSales($id, $company),
            'metrics' => $this->clientMetrics($id, $company),
        ]);
    }

    /**
     * Métricas reales del cliente para las tarjetas de resumen.
     *
     * @return array{monthly_income: float, pending_debt: float, total_paid: float}
     */
    private function clientMetrics(string $clientId, string $companyId): array
    {
        $sales = Sale::query()
            ->where('company_id', $companyId)
            ->where('client_id', $clientId);

        $monthlyIncome = (float) (clone $sales)->where('status', Sale::STATUS_ACTIVE)->sum('price');
        $pendingDebt = (float) (clone $sales)->where('status', Sale::STATUS_EXPIRED)->sum('price');
        $salesTotal = (float) (clone $sales)->sum('price');

        $renewalsTotal = (float) SaleRenewal::query()
            ->whereHas('sale', fn (Builder $query) => $query
                ->where('company_id', $companyId)
                ->where('client_id', $clientId))
            ->sum('price');

        return [
            'monthly_income' => $monthlyIncome,
            'pending_debt' => $pendingDebt,
            'total_paid' => $salesTotal + $renewalsTotal,
        ];
    }

    /**
     * Ventas vigentes del cliente (activas y expiradas, no las expulsadas),
     * enriquecidas con servicio y perfiles/cuentas para mostrar sus perfiles reales.
     *
     * @return array<int, array<string, mixed>>
     */
    private function clientSales(string $clientId, string $companyId): array
    {
        $sales = Sale::query()
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->whereIn('status', [Sale::STATUS_ACTIVE, Sale::STATUS_EXPIRED])
            ->with(['service:id,name,code', 'saleProfiles.profile.account'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->orderBy('end_date', 'desc')
            ->get();

        return SaleResource::collection($sales)->resolve();
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('clients.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('clients/edit', [
            'client' => $model,
        ]);
    }
}
