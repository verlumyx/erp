<?php

declare(strict_types=1);

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Models\Profile;
use App\Modules\Client\Models\Client;
use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Commands\SearchSaleCommand;
use App\Modules\Sale\Resources\SaleResource;
use App\Modules\Sale\Services\SaleFindService;
use App\Modules\Sale\Services\SaleSearchService;
use App\Modules\Service\Models\Service;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleGetController extends Controller
{
    public function __construct(
        private readonly SaleSearchService $searchService,
        private readonly SaleFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales.list') ?? false, 403);

        $companyId = session('current_company_id');

        $filters = $request->only(['code', 'status', 'client_id', 'agent_id', 'service_id', 'date_from', 'date_to', 'expiring_soon']);

        $command = new SearchSaleCommand(
            filters: $filters,
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $companyId,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('sales/index', [
            'sales' => SaleResource::collection($result['data'])->resolve(),
            'clients' => $this->companyClients($companyId),
            'services' => $this->companyServices($companyId),
            'agents' => $this->companyAgents($companyId),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => array_merge($filters, $request->only(['limit', 'offset'])),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales.create') ?? false, 403);

        $companyId = session('current_company_id');

        $requestedClientId = $request->query('client');
        $requestedClientId = is_string($requestedClientId) && $requestedClientId !== '' ? $requestedClientId : null;

        $clients = $this->activeClients($companyId, $requestedClientId);
        $preselectedClientId = $requestedClientId !== null && collect($clients)->contains('id', $requestedClientId)
            ? $requestedClientId
            : null;

        return Inertia::render('sales/create', [
            'clients' => $clients,
            'plans' => $this->activePlans($companyId),
            'availableProfiles' => $this->availableProfiles($companyId),
            'preselectedClientId' => $preselectedClientId,
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales/show', [
            'sale' => (new SaleResource($model))->resolve(),
            'availableProfiles' => [],
        ]);
    }

    /**
     * Clientes (todos) de la compañía, para los filtros del listado.
     *
     * @return array<int, array{id: string, name: string, code: ?string, status: string}>
     */
    private function companyClients(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Client::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'status'])
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->code,
                'status' => $client->status,
            ])
            ->all();
    }

    /**
     * Lote inicial de clientes activos (máx. 20) para el paso 1 del wizard. La
     * búsqueda completa se resuelve server-side vía sales.clients.search, evitando
     * cargar todos los clientes de la compañía. Si se pasa un cliente preseleccionado
     * (venta iniciada desde la ficha del cliente) se ancla al inicio del lote aunque
     * no esté entre los primeros 20.
     *
     * @return array<int, array{id: string, name: string, code: ?string}>
     */
    private function activeClients(?string $companyId, ?string $preselectedClientId = null): array
    {
        if ($companyId === null) {
            return [];
        }

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code']);

        if ($preselectedClientId !== null && ! $clients->contains('id', $preselectedClientId)) {
            $preselected = Client::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->whereKey($preselectedClientId)
                ->first(['id', 'name', 'code']);

            if ($preselected !== null) {
                $clients->prepend($preselected);
            }
        }

        return $clients
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->code,
            ])
            ->all();
    }

    /**
     * Planes activos con su snapshot (servicio, capacidad, duración, precio),
     * para el paso 2 del wizard.
     *
     * @return array<int, array<string, mixed>>
     */
    private function activePlans(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Plan::query()
            ->with('service:id,name,max_profiles')
            ->where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'service_id' => $plan->service_id,
                'service_name' => $plan->service?->name,
                'max_profiles' => (int) ($plan->service?->max_profiles ?? 0),
                'capacity' => $plan->capacity,
                'duration_days' => $plan->duration_days,
                'sale_price' => $plan->sale_price,
            ])
            ->all();
    }

    /**
     * Profiles disponibles de la compañía, agrupables por servicio/cuenta en el frontend.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availableProfiles(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Profile::query()
            ->where('status', 'available')
            ->whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->with('account:id,code,email,service_id')
            ->get()
            ->map(fn (Profile $profile): array => [
                'id' => $profile->id,
                'number' => $profile->number,
                'account_id' => $profile->account_id,
                'account_code' => $profile->account?->code,
                'account_email' => $profile->account?->email,
                'service_id' => $profile->account?->service_id,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string, code: ?string}>
     */
    private function companyServices(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Service::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
            ])
            ->all();
    }

    /**
     * Usuarios (agentes) de la compañía, para el filtro por agente del listado.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function companyAgents(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return User::query()
            ->whereHas('companies', fn ($q) => $q->where('app_companies.id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }
}
