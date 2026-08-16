<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesOrder\Commands\SearchSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Resources\SalesOrderResource;
use App\Modules\SalesOrder\Services\SalesOrderFindService;
use App\Modules\SalesOrder\Services\SalesOrderFormOptionsService;
use App\Modules\SalesOrder\Services\SalesOrderSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderGetController extends Controller
{
    /** Claves de filtro que acepta el listado. */
    private const FILTER_KEYS = [
        'code', 'client', 'client_id', 'warehouse_id', 'salesperson_id',
        'client_reference', 'currency', 'order_date_from', 'order_date_to', 'status',
    ];

    public function __construct(
        private readonly SalesOrderSearchService $searchService,
        private readonly SalesOrderFindService $findService,
        private readonly SalesOrderFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales-orders.list') ?? false, 403);

        $command = new SearchSalesOrderCommand(
            filters: $request->only(self::FILTER_KEYS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('sales-orders/index', [
            'salesOrders' => array_map(
                fn (SalesOrder $order): array => (new SalesOrderResource($order))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTER_KEYS, 'limit', 'offset']),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales-orders.create') ?? false, 403);

        return Inertia::render('sales-orders/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-orders.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-orders/show', [
            'salesOrder' => (new SalesOrderResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-orders.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-orders/edit', [
            'salesOrder' => (new SalesOrderResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
