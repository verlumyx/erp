<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ItemStock\Commands\SearchItemStockCommand;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Resources\ItemStockResource;
use App\Modules\ItemStock\Services\ItemStockFindService;
use App\Modules\ItemStock\Services\ItemStockSearchService;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Services\WarehouseSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Existencias es una tabla derivada: solo se consulta.
 *
 * No hay `create` ni `edit` porque el saldo no se captura — lo mueven los
 * documentos a través de `ItemStockApplyMovementService`, y se corrige con un
 * Ajuste, nunca editando la fila.
 */
class ItemStockGetController extends Controller
{
    /** Tope de registros cargados para los selectores del filtro. */
    private const OPTIONS_LIMIT = 500;

    /** @var array<int, string> */
    private const FILTER_KEYS = ['q', 'item_id', 'warehouse_id', 'location_id', 'lot_id', 'status', 'with_stock'];

    public function __construct(
        private readonly ItemStockSearchService $searchService,
        private readonly ItemStockFindService $findService,
        private readonly WarehouseSearchService $warehouseSearchService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('item-stocks.list') ?? false, 403);

        $command = new SearchItemStockCommand(
            filters: $request->only(self::FILTER_KEYS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('item-stocks/index', [
            'stocks' => array_map(
                fn (ItemStock $stock): array => (new ItemStockResource($stock))->resolve(),
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
        abort_unless(request()->user()?->hasPermission('item-stocks.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('item-stocks/show', [
            'stock' => (new ItemStockResource($model))->resolve(),
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
