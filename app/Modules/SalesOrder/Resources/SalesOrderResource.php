<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'client_code' => $this->whenLoaded('client', fn () => $this->client?->code),
            'client_address_id' => $this->client_address_id,
            'client_address_name' => $this->whenLoaded('clientAddress', fn () => $this->clientAddress?->name),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'price_list_id' => $this->price_list_id,
            'price_list_name' => $this->whenLoaded('priceList', fn () => $this->priceList?->name),
            'salesperson_id' => $this->salesperson_id,
            'salesperson_name' => $this->whenLoaded('salesperson', fn () => $this->salesperson?->name),
            'route_id' => $this->route_id,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'expected_date' => $this->expected_date?->format('Y-m-d'),
            'client_reference' => $this->client_reference,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'payment_term_days' => $this->payment_term_days,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'dispatched_percent' => $this->dispatched_percent,
            'invoiced_percent' => $this->invoiced_percent,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => SalesOrderLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
