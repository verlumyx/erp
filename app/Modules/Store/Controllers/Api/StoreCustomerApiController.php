<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateStoreCustomer;
use App\Http\Middleware\AuthenticateStoreKey;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Store\Commands\SearchStoreOrderCommand;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Resources\Api\StoreCustomerApiResource;
use App\Modules\Store\Services\StoreOrderSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lo que el comprador ve de su cuenta: sus datos, las direcciones de su
 * cliente si está vinculado, y sus pedidos.
 */
class StoreCustomerApiController extends Controller
{
    private const ORDERS_LIMIT = 100;

    public function __construct(
        private readonly StoreOrderSearchService $orderSearchService,
    ) {}

    public function me(Request $request): JsonResponse
    {
        $customer = $this->customer($request);
        $customer->loadMissing('client.addresses');

        $addresses = $customer->isLinked() && $customer->client !== null
            ? $customer->client->addresses
                ->where('status', 'active')
                ->map(fn (ClientAddress $address): array => [
                    'id' => $address->id,
                    'name' => $address->name,
                    'address' => $address->address,
                    'city' => $address->city,
                    'state' => $address->state,
                    'is_default' => $address->is_default,
                ])
                ->values()
                ->all()
            : [];

        return response()->json([
            'data' => [
                ...(new StoreCustomerApiResource($customer))->resolve(),
                'addresses' => $addresses,
            ],
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        $result = $this->orderSearchService->execute(new SearchStoreOrderCommand(
            filters: ['store_customer_id' => $customer->id],
            limit: self::ORDERS_LIMIT,
            companyId: (string) $request->attributes->get(AuthenticateStoreKey::REQUEST_ATTRIBUTE),
        ));

        return response()->json([
            'data' => array_map(fn (StoreOrder $order): array => [
                'id' => $order->id,
                'code' => $order->code,
                'status' => $order->status,
                'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
                'currency' => $order->currency,
                'subtotal' => (string) $order->subtotal,
                'total' => (string) $order->total,
                'sales_order_code' => $order->salesOrder?->code,
                'rejection_reason' => $order->rejection_reason,
            ], $result['data']),
        ]);
    }

    private function customer(Request $request): StoreCustomer
    {
        return $request->attributes->get(AuthenticateStoreCustomer::REQUEST_ATTRIBUTE);
    }
}
