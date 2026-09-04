<?php

declare(strict_types=1);

namespace App\Modules\Import\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportResource extends JsonResource
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
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'import_date' => $this->import_date?->format('Y-m-d'),
            'arrival_date' => $this->arrival_date?->format('Y-m-d'),
            'reference' => $this->reference,
            'allocation_method' => $this->allocation_method,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'total_charges' => $this->total_charges,
            'total_base_value' => $this->total_base_value,
            'total_landed_value' => $this->total_landed_value,
            'capitalized_amount' => $this->capitalized_amount,
            'variance_amount' => $this->variance_amount,
            'adjustment_id' => $this->adjustment_id,
            'adjustment_code' => $this->whenLoaded('adjustment', fn () => $this->adjustment?->code),
            'adjustment_status' => $this->whenLoaded('adjustment', fn () => $this->adjustment?->status),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_by_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'costs' => $this->whenLoaded(
                'costs',
                fn (): array => ImportCostResource::collection($this->costs)->resolve($request),
            ),
            'entries' => $this->whenLoaded(
                'entries',
                fn (): array => ImportEntryResource::collection($this->entries)->resolve($request),
            ),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => ImportLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
