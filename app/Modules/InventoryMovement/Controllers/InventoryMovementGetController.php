<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Resources\InventoryMovementResource;
use App\Modules\InventoryMovement\Services\InventoryMovementFindService;
use App\Modules\InventoryMovement\Services\InventoryMovementSearchService;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Services\WarehouseSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El kardex solo se consulta.
 *
 * No hay `create`, `store`, `update` ni `status`: el movimiento lo escribe el
 * documento que afecta el inventario a través de
 * `InventoryMovementRegisterService`, y un error se corrige anulando ese
 * documento —que emite la contrapartida— nunca desde esta pantalla.
 */
class InventoryMovementGetController extends Controller
{
    /** Tope de registros cargados para los selectores del filtro. */
    private const OPTIONS_LIMIT = 500;

    /** @var array<int, string> */
    private const FILTER_KEYS = [
        'q',
        'item_id',
        'warehouse_id',
        'lot_id',
        'type',
        'direction',
        'origin_type',
        'status',
        'date_from',
        'date_to',
    ];

    public function __construct(
        private readonly InventoryMovementSearchService $searchService,
        private readonly InventoryMovementFindService $findService,
        private readonly WarehouseSearchService $warehouseSearchService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('inventory-movements.list') ?? false, 403);

        $command = new SearchInventoryMovementCommand(
            filters: $request->only(self::FILTER_KEYS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('inventory-movements/index', [
            'movements' => array_map(
                fn (InventoryMovement $movement): array => (new InventoryMovementResource($movement))->resolve(),
                $result['data'],
            ),
            'warehouses' => $this->warehouseOptions(),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTER_KEYS, 'limit', 'offset']),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('inventory-movements.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('inventory-movements/show', [
            'movement' => (new InventoryMovementResource($model))->resolve(),
        ]);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function warehouseOptions(): array
    {
        $result = $this->warehouseSearchService->execute(new SearchWarehouseCommand(
            filters: ['status' => 'active'],
            limit: self::OPTIONS_LIMIT,
            offset: 0,
            companyId: session('current_company_id'),
        ));

        return array_map(
            fn (Warehouse $warehouse): array => ['id' => $warehouse->id, 'name' => $warehouse->name],
            $result['data'],
        );
    }
}
