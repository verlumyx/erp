<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Models\Client;
use App\Modules\Store\Commands\SearchStoreOrderCommand;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;
use App\Modules\Store\Resources\StoreOrderResource;
use App\Modules\Store\Services\StoreOrderFindService;
use App\Modules\Store\Services\StoreOrderSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bandeja de pedidos web. Sin `create` ni `edit`: el pedido lo arma el
 * comprador y aquí solo se convierte o se rechaza.
 */
class StoreOrderGetController extends Controller
{
    public function __construct(
        private readonly StoreOrderSearchService $searchService,
        private readonly StoreOrderFindService $findService,
        private readonly StoreOrderRepositoryInterface $orders,
        private readonly StoreCustomerRepositoryInterface $customers,
    ) {}

    public function index(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('store-orders.list') ?? false, 403);

        $command = new SearchStoreOrderCommand(
            filters: $request->only(['status', 'q', 'date_from', 'date_to']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('store/orders/index', [
            'store_orders' => array_map(
                fn (StoreOrder $order): array => (new StoreOrderResource($order))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['status', 'q', 'date_from', 'date_to', 'limit', 'offset']),
            'pending_count' => $this->orders->countPending($company),
        ]);
    }

    /**
     * Junto al pedido viaja lo que necesita el diálogo de conversión de un
     * comprador sin vínculo: el cliente sugerido por correo y si al comprador
     * le falta el RIF.
     */
    public function show(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('store-orders.show') ?? false, 403);

        $order = $this->findService->execute($id, $company);
        $customer = $order->storeCustomer;

        $suggested = $customer === null || $customer->isLinked()
            ? null
            : $this->customers->findClientByEmail($company, $customer->email);

        return Inertia::render('store/orders/show', [
            'store_order' => (new StoreOrderResource($order))->resolve(),
            'suggested_client' => $suggested instanceof Client ? [
                'value' => $suggested->id,
                'label' => $suggested->code ? "{$suggested->code} — {$suggested->name}" : $suggested->name,
                'meta' => ['code' => $suggested->code, 'name' => $suggested->name],
            ] : null,
            'customer_needs_document' => $customer !== null
                && ($customer->document_type === null || $customer->document_number === null),
        ]);
    }
}
