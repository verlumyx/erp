<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources;

use App\Modules\Store\Models\StoreOrderLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreOrderResource extends JsonResource
{
    /**
     * `needs_review` viaja resuelto: el pedido de un cliente inactivo o con
     * crédito bloqueado entra marcado, y el bloqueo real ocurre al confirmar
     * la orden.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $client = $this->relationLoaded('client') ? $this->client : null;

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'status' => $this->status,
            'store_customer_id' => $this->store_customer_id,
            'store_customer' => $this->whenLoaded('storeCustomer', fn (): ?array => $this->storeCustomer === null ? null : [
                'id' => $this->storeCustomer->id,
                'code' => $this->storeCustomer->code,
                'name' => $this->storeCustomer->name,
                'email' => $this->storeCustomer->email,
                'client_id' => $this->storeCustomer->client_id,
            ]),
            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn (): ?array => $client === null ? null : [
                'id' => $client->id,
                'code' => $client->code,
                'name' => $client->name,
                'price_list_id' => $client->price_list_id,
                'salesperson_id' => $client->salesperson_id,
                'status' => $client->status,
                'credit_blocked' => $client->credit_blocked,
            ]),
            'client_address_id' => $this->client_address_id,
            'client_address' => $this->whenLoaded('clientAddress', fn (): ?array => $this->clientAddress === null ? null : [
                'id' => $this->clientAddress->id,
                'name' => $this->clientAddress->name,
                'address' => $this->clientAddress->address,
            ]),
            'sales_order_id' => $this->sales_order_id,
            'sales_order' => $this->whenLoaded('salesOrder', fn (): ?array => $this->salesOrder === null ? null : [
                'id' => $this->salesOrder->id,
                'code' => $this->salesOrder->code,
                'status' => $this->salesOrder->status,
            ]),
            'buyer_name' => $this->buyer_name,
            'buyer_document_type' => $this->buyer_document_type,
            'buyer_document_number' => $this->buyer_document_number,
            'buyer_email' => $this->buyer_email,
            'buyer_phone' => $this->buyer_phone,
            'delivery_address' => $this->delivery_address,
            'delivery_city' => $this->delivery_city,
            'delivery_state' => $this->delivery_state,
            'currency' => $this->currency,
            'exchange_rate' => (string) $this->exchange_rate,
            'subtotal' => (string) $this->subtotal,
            'total' => (string) $this->total,
            'buyer_notes' => $this->buyer_notes,
            'notes' => $this->notes,
            'converted_by' => $this->converted_by,
            'converter' => $this->whenLoaded('converter', fn (): ?array => $this->converter === null ? null : [
                'id' => $this->converter->id,
                'name' => $this->converter->name,
            ]),
            'converted_at' => $this->converted_at?->format('Y-m-d H:i:s'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,
            'needs_review' => $client !== null && ($client->status !== 'active' || $client->credit_blocked === 'yes'),
            'lines' => $this->whenLoaded('lines', fn (): array => $this->lines
                ->map(fn (StoreOrderLine $line): array => (new StoreOrderLineResource($line))->resolve())
                ->values()
                ->all()),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
