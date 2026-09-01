<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
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
            'origin_warehouse_id' => $this->origin_warehouse_id,
            'origin_warehouse_name' => $this->whenLoaded('originWarehouse', fn () => $this->originWarehouse?->name),
            'destination_warehouse_id' => $this->destination_warehouse_id,
            'destination_warehouse_name' => $this->whenLoaded('destinationWarehouse', fn () => $this->destinationWarehouse?->name),
            'transit_warehouse_id' => $this->transit_warehouse_id,
            'transit_warehouse_name' => $this->whenLoaded('transitWarehouse', fn () => $this->transitWarehouse?->name),
            'transfer_date' => $this->transfer_date?->format('Y-m-d'),
            'expected_date' => $this->expected_date?->format('Y-m-d'),
            'received_date' => $this->received_date?->format('Y-m-d'),
            'reason' => $this->reason,
            'reason_detail' => $this->reason_detail,
            'driver_id' => $this->driver_id,
            'driver_name' => $this->whenLoaded('driver', fn () => $this->driver?->name),
            'vehicle_plate' => $this->vehicle_plate,
            'route_id' => $this->route_id,
            'total_quantity' => $this->total_quantity,
            'total_cost' => $this->total_cost,
            'transfer_status' => $this->transfer_status,
            'sent_by' => $this->sent_by,
            'sent_by_name' => $this->whenLoaded('sender', fn () => $this->sender?->name),
            'received_by' => $this->received_by,
            'received_by_name' => $this->whenLoaded('receiver', fn () => $this->receiver?->name),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => TransferLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
