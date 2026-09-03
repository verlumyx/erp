<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\SearchStoreCustomerCommand;
use App\Modules\Store\Commands\SearchStoreOrderCommand;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use App\Modules\Store\Resources\StoreCustomerResource;
use App\Modules\Store\Resources\StoreOrderResource;
use App\Modules\Store\Services\StoreCustomerFindService;
use App\Modules\Store\Services\StoreCustomerSearchService;
use App\Modules\Store\Services\StoreOrderSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Compradores de la tienda. Sin `create` ni `edit`: el comprador nace al
 * registrarse en la tienda o al invitarlo desde el cliente, y sus datos los
 * mantiene él.
 */
class StoreCustomerGetController extends Controller
{
    private const CUSTOMER_ORDERS_LIMIT = 100;

    public function __construct(
        private readonly StoreCustomerSearchService $searchService,
        private readonly StoreCustomerFindService $findService,
        private readonly StoreOrderSearchService $orderSearchService,
        private readonly StoreCustomerRepositoryInterface $repository,
    ) {}

    public function index(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('store-customers.list') ?? false, 403);

        $command = new SearchStoreCustomerCommand(
            filters: $request->only(['q', 'status', 'linked']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('store/customers/index', [
            'store_customers' => array_map(
                fn (StoreCustomer $customer): array => (new StoreCustomerResource($customer))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['q', 'status', 'linked', 'limit', 'offset']),
        ]);
    }

    public function show(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('store-customers.show') ?? false, 403);

        $customer = $this->findService->execute($id, $company);

        $orders = $this->orderSearchService->execute(new SearchStoreOrderCommand(
            filters: ['store_customer_id' => $customer->id],
            limit: self::CUSTOMER_ORDERS_LIMIT,
            companyId: $company,
        ));

        return Inertia::render('store/customers/show', [
            'store_customer' => (new StoreCustomerResource($customer))->resolve(),
            'store_orders' => array_map(
                fn (StoreOrder $order): array => (new StoreOrderResource($order))->resolve(),
                $orders['data'],
            ),
        ]);
    }

    /**
     * El comprador vinculado a un cliente, para la tarjeta «Tienda» de la
     * pantalla del cliente. JSON: la pantalla lo pide por `fetch` para no
     * tocar el controlador de clientes.
     */
    public function byClient(Request $request, string $company, string $client): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('store-customers.show') ?? false, 403);

        $customer = $this->repository->findByClient($client);

        if ($customer !== null && $customer->company_id !== $company) {
            $customer = null;
        }

        return response()->json([
            'data' => $customer === null ? null : (new StoreCustomerResource($customer))->resolve(),
        ]);
    }
}
