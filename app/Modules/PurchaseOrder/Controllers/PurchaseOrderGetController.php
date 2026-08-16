<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseOrder\Commands\SearchPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Resources\PurchaseOrderResource;
use App\Modules\PurchaseOrder\Services\PurchaseOrderFindService;
use App\Modules\PurchaseOrder\Services\PurchaseOrderFormOptionsService;
use App\Modules\PurchaseOrder\Services\PurchaseOrderSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'supplier_id', 'warehouse_id', 'supplier_reference', 'status', 'date_from', 'date_to',
    ];

    public function __construct(
        private readonly PurchaseOrderSearchService $searchService,
        private readonly PurchaseOrderFindService $findService,
        private readonly PurchaseOrderFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-orders.list') ?? false, 403);

        $command = new SearchPurchaseOrderCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('purchase-orders/index', [
            'purchaseOrders' => array_map(
                fn (PurchaseOrder $order): array => (new PurchaseOrderResource($order))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTERS, 'limit', 'offset']),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-orders.create') ?? false, 403);

        return Inertia::render('purchase-orders/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-orders.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-orders/show', [
            'purchaseOrder' => (new PurchaseOrderResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-orders.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-orders/edit', [
            'purchaseOrder' => (new PurchaseOrderResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
