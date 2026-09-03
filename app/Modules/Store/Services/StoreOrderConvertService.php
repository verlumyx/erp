<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Client\Models\Client;
use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Commands\SalesOrderLineData;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Services\SalesOrderCreateService;
use App\Modules\Store\Commands\ConvertStoreOrderCommand;
use App\Modules\Store\Exceptions\ClientAlreadyLinkedException;
use App\Modules\Store\Exceptions\StoreOrderNotPendingException;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Models\StoreOrderLine;
use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Convierte el pedido web en una orden de venta en borrador. Servicio espejo
 * a `SalesOrderCreateService`: arma el comando y lo llama, sin duplicar la
 * lógica de la orden. Control de crédito, reserva y aprobación se aplican al
 * confirmar la orden, no aquí.
 *
 * Todo ocurre en una transacción: la creación del cliente, el vínculo del
 * comprador, la dirección nueva, la orden y el cierre del pedido web.
 */
class StoreOrderConvertService
{
    public function __construct(
        private readonly StoreOrderRepositoryInterface $orders,
        private readonly StoreCustomerRepositoryInterface $customers,
        private readonly StoreOrderFindService $findService,
        private readonly StoreSettingFindOrCreateService $settings,
        private readonly StoreClientFromCustomerService $clientFromCustomer,
        private readonly SalesOrderCreateService $salesOrders,
    ) {}

    /**
     * @return array{ order: StoreOrder, sales_order: SalesOrder, notice: ?string }
     *
     * @throws ValidationException
     */
    public function execute(ConvertStoreOrderCommand $command): array
    {
        $order = $this->findService->execute($command->orderId, $command->companyId);

        if ($order->status !== 'pending') {
            throw StoreOrderNotPendingException::forOrder((string) $order->code);
        }

        $settings = $this->settings->execute($command->companyId, $command->userId);

        return DB::transaction(function () use ($order, $settings, $command): array {
            $customer = $this->customers->findOrFail((string) $order->store_customer_id, $command->companyId);
            $client = $this->resolveClient($settings, $customer, $command);

            $addressId = $order->client_address_id
                ?? ($order->delivery_address === null ? null : $this->orders->createDeliveryAddress($order, $client->id)->id);

            $salesOrderId = (string) Str::uuid7();

            $this->salesOrders->execute(new CreateSalesOrderCommand(
                id: $salesOrderId,
                companyId: $command->companyId,
                clientId: $client->id,
                warehouseId: $this->resolveWarehouse($settings, $command->companyId),
                orderDate: now()->toDateString(),
                createdBy: $command->userId,
                lines: $this->lines($order),
                clientAddressId: $addressId,
                priceListId: $client->price_list_id ?? $settings->price_list_id,
                salespersonId: $client->salesperson_id ?? $command->userId,
                clientReference: (string) $order->code,
                currency: (string) $order->currency,
                exchangeRateOverride: null,
                paymentTermDays: (int) $client->payment_term_days,
                notes: $order->buyer_notes === null ? null : "Comentario del comprador: {$order->buyer_notes}",
            ));

            $this->orders->writeConverted($order, $salesOrderId, $client->id, $command->userId);

            $salesOrder = SalesOrder::query()->with('lines')->findOrFail($salesOrderId);

            return [
                'order' => $this->orders->findOrFail($order->id, $command->companyId),
                'sales_order' => $salesOrder,
                'notice' => $this->priceNotice($order, $salesOrder),
            ];
        });
    }

    /**
     * El cliente de la orden. Un comprador vinculado ya lo trae; uno sin
     * vínculo lo elige o lo crea aquí, y el vínculo queda guardado para que
     * nunca vuelva a preguntarse.
     *
     * @throws ValidationException
     */
    private function resolveClient(StoreSetting $settings, StoreCustomer $customer, ConvertStoreOrderCommand $command): Client
    {
        if ($customer->isLinked()) {
            return $this->customers->findClientById((string) $customer->client_id, $command->companyId)
                ?? throw ValidationException::withMessages(['client_id' => 'El cliente vinculado ya no existe.']);
        }

        if ($command->createClient === 'yes') {
            $this->writeDocumentIfGiven($customer, $command);

            $client = $this->clientFromCustomer->execute($settings, $customer, $command->userId);
        } else {
            if ($command->clientId === null) {
                throw ValidationException::withMessages([
                    'client_id' => 'Elige el cliente del comprador o crea uno con sus datos.',
                ]);
            }

            $client = $this->customers->findClientById($command->clientId, $command->companyId);

            if (! $client instanceof Client) {
                throw ValidationException::withMessages([
                    'client_id' => 'El cliente no existe o no pertenece a esta empresa.',
                ]);
            }

            if ($this->customers->findByClient($client->id) !== null) {
                throw ClientAlreadyLinkedException::forClient($client->name);
            }
        }

        $this->customers->writeLink($customer, $client->id, $command->userId, 'conversion');

        return $client;
    }

    private function writeDocumentIfGiven(StoreCustomer $customer, ConvertStoreOrderCommand $command): void
    {
        if ($command->documentType === null || $command->documentNumber === null) {
            return;
        }

        $owner = $this->customers->findByDocument($command->companyId, $command->documentType, $command->documentNumber);

        if ($owner !== null && $owner->id !== $customer->id) {
            throw ValidationException::withMessages([
                'document_number' => 'Otro comprador ya tiene ese RIF.',
            ]);
        }

        $this->customers->writeDocument($customer, $command->documentType, $command->documentNumber);
        $customer->refresh();
    }

    /**
     * @throws ValidationException
     */
    private function resolveWarehouse(StoreSetting $settings, string $companyId): string
    {
        $warehouseId = $settings->warehouse_id ?? $this->orders->firstActiveWarehouseId($companyId);

        if ($warehouseId === null) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'La empresa no tiene bodegas activas para recibir la orden.',
            ]);
        }

        return (string) $warehouseId;
    }

    /**
     * Las líneas del pedido web como líneas de la orden. El precio web va
     * como precio y como lista a la vez: así el repositorio de la orden lo
     * sustituye por el de la lista resuelta. El impuesto es el del artículo,
     * como en una orden capturada a mano.
     *
     * @return array<int, SalesOrderLineData>
     */
    private function lines(StoreOrder $order): array
    {
        $lines = $order->lines->where('status', 'active')->values();
        $taxes = $this->orders->taxRates(
            $lines->map(fn (StoreOrderLine $line): ?string => $line->item?->sale_tax_id)->filter()->values()->all(),
        );

        return $lines->map(function (StoreOrderLine $line) use ($taxes): SalesOrderLineData {
            $taxId = $line->item?->sale_tax_id;
            $tax = $taxId === null ? null : ($taxes[$taxId] ?? null);

            return new SalesOrderLineData(
                id: null,
                itemId: (string) $line->item_id,
                measurementUnitId: (string) $line->measurement_unit_id,
                quantity: (string) $line->quantity,
                unitPrice: (string) $line->unit_price,
                listPrice: (string) $line->unit_price,
                discountPercent: '0',
                taxId: $tax === null ? null : $taxId,
                taxPercent: $tax['percentage'] ?? '0',
                withholdingPercent: $tax['withholding_percentage'] ?? '0',
            );
        })->all();
    }

    /**
     * Aviso cuando la lista resuelta trae un precio distinto del que vio el
     * comprador. No bloquea: el precio pudo cambiar entre el pedido y la
     * conversión, y la orden nace en borrador para poder ajustarlo.
     */
    private function priceNotice(StoreOrder $order, SalesOrder $salesOrder): ?string
    {
        $webPrices = $order->lines->keyBy('item_id');
        $changed = [];

        foreach ($salesOrder->lines as $line) {
            /** @var SalesOrderLine $line */
            $web = $webPrices->get($line->item_id);

            if ($web === null) {
                continue;
            }

            if (round((float) $web->unit_price, 2) !== round((float) $line->unit_price, 2)) {
                $changed[] = sprintf('%s (%s → %s)', $web->item?->name ?? $line->item_id, $web->unit_price, $line->unit_price);
            }
        }

        if ($changed === []) {
            return null;
        }

        return 'El precio de lista cambió desde el pedido web: '.implode(', ', $changed).'.';
    }
}
