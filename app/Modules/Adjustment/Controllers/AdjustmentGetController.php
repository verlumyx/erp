<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Adjustment\Commands\SearchAdjustmentCommand;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Resources\AdjustmentResource;
use App\Modules\Adjustment\Services\AdjustmentFindService;
use App\Modules\Adjustment\Services\AdjustmentFormOptionsService;
use App\Modules\Adjustment\Services\AdjustmentSearchService;
use App\Modules\Adjustment\Services\AdjustmentStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdjustmentGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'warehouse_id', 'type', 'direction', 'count_id',
        'approved_by', 'status', 'date_from', 'date_to',
    ];

    public function __construct(
        private readonly AdjustmentSearchService $searchService,
        private readonly AdjustmentFindService $findService,
        private readonly AdjustmentFormOptionsService $formOptionsService,
        private readonly AdjustmentStockService $stockService,
    ) {}

    /**
     * Qué dice el sistema que hay de una existencia concreta.
     *
     * La pantalla lo consulta al elegir el artículo, la ubicación o el lote de
     * una línea: contra ese número se compara lo que se cuenta, y el usuario
     * tiene que verlo antes de escribir nada. El valor que se guarda lo vuelve
     * a resolver el backend al guardar, así que esto es solo lo que se enseña.
     */
    public function stock(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('adjustments.list') ?? false, 403);

        $warehouseId = $request->string('warehouse_id')->toString();
        $itemId = $request->string('item_id')->toString();
        $unitId = $request->string('measurement_unit_id')->toString();

        if ($warehouseId === '' || $itemId === '' || $unitId === '') {
            return response()->json(['system_quantity' => '0', 'average_cost' => '0']);
        }

        $resolved = $this->stockService->resolveForKeys($company, $warehouseId, [[
            'item_id' => $itemId,
            'measurement_unit_id' => $unitId,
            'location_id' => $request->input('location_id'),
            'lot_id' => $request->input('lot_id'),
        ]]);

        return response()->json([
            'system_quantity' => (string) $resolved[0]['system'],
            'average_cost' => (string) $resolved[0]['average'],
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('adjustments.list') ?? false, 403);

        $command = new SearchAdjustmentCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('adjustments/index', [
            'adjustments' => array_map(
                fn (Adjustment $adjustment): array => (new AdjustmentResource($adjustment))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTERS, 'limit', 'offset']),
            /** Las bodegas alimentan el filtro del listado; son pocas por empresa. */
            'warehouses' => $this->formOptionsService->warehouses(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('adjustments.create') ?? false, 403);

        return Inertia::render('adjustments/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('adjustments.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('adjustments/show', [
            'adjustment' => (new AdjustmentResource($model))->resolve(),
            'canApprove' => request()->user()?->hasPermission('adjustments.approve') ?? false,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('adjustments.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('adjustments/edit', [
            'adjustment' => (new AdjustmentResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
