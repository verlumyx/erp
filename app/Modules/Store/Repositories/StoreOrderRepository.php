<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories;

use App\Modules\Client\Models\ClientAddress;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Store\Commands\CreateStoreOrderCommand;
use App\Modules\Store\Commands\SearchStoreOrderCommand;
use App\Modules\Store\Commands\StoreOrderLineData;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Models\StoreOrderLine;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StoreOrderRepository extends StoreOrderFilters implements StoreOrderRepositoryInterface
{
    private const DETAIL_RELATIONS = [
        'lines.item',
        'lines.storeItem',
        'lines.measurementUnit',
        'storeCustomer',
        'client',
        'clientAddress',
        'salesOrder',
        'converter',
    ];

    public function create(CreateStoreOrderCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $order = StoreOrder::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'store_customer_id' => $command->storeCustomerId,
                'client_id' => $command->clientId,
                'client_address_id' => $command->clientAddressId,
                'buyer_name' => $command->buyerName,
                'buyer_document_type' => $command->buyerDocumentType,
                'buyer_document_number' => $command->buyerDocumentNumber,
                'buyer_email' => $command->buyerEmail,
                'buyer_phone' => $command->buyerPhone,
                'delivery_address' => $command->deliveryAddress,
                'delivery_city' => $command->deliveryCity,
                'delivery_state' => $command->deliveryState,
                'currency' => $command->currency,
                'exchange_rate' => $command->exchangeRate,
                'buyer_notes' => $command->buyerNotes,
                'status' => 'pending',
                'created_by' => null,
            ]);

            $lineNumber = 0;

            foreach ($command->lines as $line) {
                StoreOrderLine::create([
                    ...$this->lineAmounts($line),
                    'company_id' => $command->companyId,
                    'store_order_id' => $order->id,
                    'line_number' => ++$lineNumber,
                    'item_id' => $line->itemId,
                    'store_item_id' => $line->storeItemId,
                    'measurement_unit_id' => $line->measurementUnitId,
                    'status' => 'active',
                ]);
            }

            $this->refreshTotals($order);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?StoreOrder
    {
        return StoreOrder::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): StoreOrder
    {
        return StoreOrder::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function findByCode(string $companyId, string $code): ?StoreOrder
    {
        return StoreOrder::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->first();
    }

    /**
     * @return array{ data: StoreOrder[], total: int }
     */
    public function search(SearchStoreOrderCommand $command): array
    {
        $query = StoreOrder::query()
            ->with(['storeCustomer', 'client', 'salesOrder'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    public function countPending(string $companyId): int
    {
        return StoreOrder::query()
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->count();
    }

    public function writeConverted(StoreOrder $model, string $salesOrderId, string $clientId, string $convertedBy): void
    {
        $model->update([
            'status' => 'converted',
            'sales_order_id' => $salesOrderId,
            'client_id' => $clientId,
            'converted_by' => $convertedBy,
            'converted_at' => now(),
        ]);
    }

    public function writeRejected(StoreOrder $model, string $reason): void
    {
        $model->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<string, StoreItem>
     */
    public function visibleStoreItemsBySlug(string $companyId, array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return StoreItem::query()
            ->with('item')
            ->visibleInStore()
            ->where('company_id', $companyId)
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug')
            ->all();
    }

    /**
     * @param  array<int, string>  $itemIds
     * @return array<string, string>
     */
    public function baseUnitIdsFor(string $companyId, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return ItemUnit::query()
            ->where('company_id', $companyId)
            ->where('is_base', 'yes')
            ->where('status', 'active')
            ->whereIn('item_id', $itemIds)
            ->get()
            ->mapWithKeys(fn (ItemUnit $unit): array => [
                (string) $unit->item_id => (string) $unit->measurement_unit_id,
            ])
            ->all();
    }

    public function findActiveClientAddress(string $clientId, string $addressId): ?ClientAddress
    {
        return ClientAddress::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->find($addressId);
    }

    public function createDeliveryAddress(StoreOrder $order, string $clientId): ClientAddress
    {
        return ClientAddress::create([
            'company_id' => $order->company_id,
            'client_id' => $clientId,
            'type' => 'shipping',
            'name' => 'Tienda en línea',
            'address' => (string) $order->delivery_address,
            'city' => $order->delivery_city,
            'state' => $order->delivery_state,
            'is_default' => 'no',
            'status' => 'active',
        ]);
    }

    public function firstActiveWarehouseId(string $companyId): ?string
    {
        $id = Warehouse::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->value('id');

        return $id === null ? null : (string) $id;
    }

    /**
     * @param  array<int, string>  $taxIds
     * @return array<string, array{percentage: string, withholding_percentage: string}>
     */
    public function taxRates(array $taxIds): array
    {
        $taxIds = array_values(array_unique(array_filter($taxIds)));

        if ($taxIds === []) {
            return [];
        }

        return Tax::query()
            ->whereIn('id', $taxIds)
            ->get()
            ->mapWithKeys(fn (Tax $tax): array => [
                (string) $tax->id => [
                    'percentage' => (string) $tax->percentage,
                    'withholding_percentage' => $tax->has_withholding === 'yes' ? (string) $tax->withholding_percentage : '0',
                ],
            ])
            ->all();
    }

    /**
     * Importes de una línea. Sin descuento ni impuesto: la tienda muestra
     * precios de lista y el impuesto lo resuelve la orden de venta.
     *
     * @return array<string, string|float>
     */
    private function lineAmounts(StoreOrderLineData $line): array
    {
        $quantity = (float) $line->quantity;
        $price = (float) $line->unitPrice;
        $subtotal = round($quantity * $price, 2);

        return [
            'quantity' => $line->quantity,
            'base_quantity' => round($quantity, 4),
            'unit_price' => $price,
            'list_price' => $price,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_id' => null,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'withholding_percent' => 0,
            'withholding_amount' => 0,
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ];
    }

    private function refreshTotals(StoreOrder $order): void
    {
        $subtotal = round((float) StoreOrderLine::query()
            ->where('store_order_id', $order->id)
            ->where('status', 'active')
            ->sum('subtotal'), 2);

        $order->update([
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);
    }

    /**
     * Siguiente código secuencial por empresa (PWE000001, PWE000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = StoreOrder::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', StoreOrder::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(StoreOrder::CODE_PREFIX))) + 1
            : 1;

        return StoreOrder::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
