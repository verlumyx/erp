<?php

declare(strict_types=1);

namespace App\Modules\Entry\Resources;

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Transfer\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
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
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'supplier_code' => $this->whenLoaded('supplier', fn () => $this->supplier?->code),
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            /** Etiqueta legible del origen, para no ir a buscarlo desde la pantalla. */
            'sourceable_code' => $this->whenLoaded('sourceable', fn (): ?string => match (true) {
                $this->sourceable instanceof PurchaseOrder,
                $this->sourceable instanceof Transfer => $this->sourceable->code,
                default => null,
            }),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'entry_date' => $this->entry_date?->format('Y-m-d'),
            'entry_type' => $this->entry_type,
            'supplier_document' => $this->supplier_document,
            'carrier' => $this->carrier,
            'tracking_number' => $this->tracking_number,
            'received_by' => $this->received_by,
            'received_by_name' => $this->whenLoaded('receiver', fn () => $this->receiver?->name),
            'inspected_by' => $this->inspected_by,
            'inspected_by_name' => $this->whenLoaded('inspector', fn () => $this->inspector?->name),
            'inspection_status' => $this->inspection_status,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'total_quantity' => $this->total_quantity,
            'total_cost' => $this->total_cost,
            'is_invoiced' => $this->is_invoiced,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => EntryLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
