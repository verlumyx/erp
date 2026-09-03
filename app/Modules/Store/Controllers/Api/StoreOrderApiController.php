<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateStoreCustomer;
use App\Http\Middleware\AuthenticateStoreKey;
use App\Modules\Store\Commands\CreateStoreOrderCommand;
use App\Modules\Store\Exceptions\StoreDisabledException;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Models\StoreOrderLine;
use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;
use App\Modules\Store\Requests\Api\CreateStoreOrderApiRequest;
use App\Modules\Store\Services\StoreOrderCreateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreOrderApiController extends Controller
{
    public function __construct(
        private readonly StoreOrderCreateService $createService,
        private readonly StoreOrderRepositoryInterface $orders,
    ) {}

    public function store(CreateStoreOrderApiRequest $request): JsonResponse
    {
        /** @var StoreSetting $settings */
        $settings = $request->attributes->get(AuthenticateStoreKey::SETTINGS_ATTRIBUTE);
        $customer = $this->customer($request);

        try {
            $order = $this->createService->execute(
                $settings,
                $customer,
                CreateStoreOrderCommand::fromRequest($request, (string) $settings->company_id, $customer->id),
            );
        } catch (StoreDisabledException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json([
            'data' => [
                'id' => $order->id,
                'code' => $order->code,
                'status' => $order->status,
                'currency' => $order->currency,
                'subtotal' => (string) $order->subtotal,
                'total' => (string) $order->total,
            ],
        ], 201);
    }

    /** Estado del pedido. Solo si pertenece al comprador del token; si no, 404. */
    public function show(Request $request, string $code): JsonResponse
    {
        $companyId = (string) $request->attributes->get(AuthenticateStoreKey::REQUEST_ATTRIBUTE);
        $customer = $this->customer($request);

        $order = $this->orders->findByCode($companyId, strtoupper($code));

        if (! $order instanceof StoreOrder || $order->store_customer_id !== $customer->id) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $order->id,
                'code' => $order->code,
                'status' => $order->status,
                'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
                'currency' => $order->currency,
                'subtotal' => (string) $order->subtotal,
                'total' => (string) $order->total,
                'buyer_notes' => $order->buyer_notes,
                'delivery_address' => $order->delivery_address ?? $order->clientAddress?->address,
                'delivery_city' => $order->delivery_city ?? $order->clientAddress?->city,
                'delivery_state' => $order->delivery_state ?? $order->clientAddress?->state,
                'rejection_reason' => $order->rejection_reason,
                'sales_order_code' => $order->salesOrder?->code,
                'lines' => $order->lines
                    ->where('status', 'active')
                    ->map(fn (StoreOrderLine $line): array => [
                        'slug' => $line->storeItem?->slug,
                        'title' => $line->storeItem?->title ?? $line->item?->name,
                        'quantity' => (string) $line->quantity,
                        'unit_price' => (string) $line->unit_price,
                        'subtotal' => (string) $line->subtotal,
                        'unit' => $line->measurementUnit?->abbreviation ?? $line->measurementUnit?->name,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    private function customer(Request $request): StoreCustomer
    {
        return $request->attributes->get(AuthenticateStoreCustomer::REQUEST_ATTRIBUTE);
    }
}
