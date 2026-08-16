<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Services\WarehouseSearchService;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Resources\WarehouseLocationResource;
use App\Modules\WarehouseLocation\Services\WarehouseLocationFindService;
use App\Modules\WarehouseLocation\Services\WarehouseLocationSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseLocationGetController extends Controller
{
    /** Tope de registros cargados para los selectores del formulario. */
    private const OPTIONS_LIMIT = 500;

    public function __construct(
        private readonly WarehouseLocationSearchService $searchService,
        private readonly WarehouseLocationFindService $findService,
        private readonly WarehouseSearchService $warehouseSearchService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('warehouse-locations.list') ?? false, 403);

        $command = new SearchWarehouseLocationCommand(
            filters: $request->only(['name', 'code', 'location_code', 'warehouse_id', 'type', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('warehouse-locations/index', [
            'locations' => array_map(
                fn (WarehouseLocation $location): array => (new WarehouseLocationResource($location))->resolve(),
                $result['data'],
            ),
            'warehouses' => $this->warehouseOptions(),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'code', 'location_code', 'warehouse_id', 'type', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('warehouse-locations.create') ?? false, 403);

        return Inertia::render('warehouse-locations/create', [
            'warehouses' => $this->warehouseOptions(),
            'parents' => $this->parentOptions(),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('warehouse-locations.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('warehouse-locations/show', [
            'location' => (new WarehouseLocationResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('warehouse-locations.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('warehouse-locations/edit', [
            'location' => (new WarehouseLocationResource($model))->resolve(),
            'warehouses' => $this->warehouseOptions(),
            'parents' => $this->parentOptions(),
        ]);
    }

    /**
     * Bodegas activas que gestionan ubicaciones.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function warehouseOptions(): array
    {
        $result = $this->warehouseSearchService->execute(new SearchWarehouseCommand(
            filters: ['uses_locations' => 'yes', 'status' => 'active'],
            limit: self::OPTIONS_LIMIT,
            offset: 0,
            companyId: session('current_company_id'),
        ));

        return array_map(
            fn (Warehouse $warehouse): array => ['id' => $warehouse->id, 'name' => $warehouse->name],
            $result['data'],
        );
    }

    /**
     * Ubicaciones activas que pueden actuar como padre en el árbol.
     *
     * @return array<int, array{id: string, warehouse_id: string, name: string, location_code: string}>
     */
    private function parentOptions(): array
    {
        $result = $this->searchService->execute(new SearchWarehouseLocationCommand(
            filters: ['status' => 'active'],
            limit: self::OPTIONS_LIMIT,
            offset: 0,
            companyId: session('current_company_id'),
        ));

        return array_map(
            fn (WarehouseLocation $location): array => [
                'id' => $location->id,
                'warehouse_id' => $location->warehouse_id,
                'name' => $location->name,
                'location_code' => $location->location_code,
            ],
            $result['data'],
        );
    }
}
