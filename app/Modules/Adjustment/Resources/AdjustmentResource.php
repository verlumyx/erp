<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentResource extends JsonResource
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
            'adjustment_date' => $this->adjustment_date?->format('Y-m-d'),
            'type' => $this->type,
            'direction' => $this->direction,
            'reason' => $this->reason,
            'count_id' => $this->count_id,
            'total_quantity_in' => $this->total_quantity_in,
            'total_quantity_out' => $this->total_quantity_out,
            'total_cost_in' => $this->total_cost_in,
            'total_cost_out' => $this->total_cost_out,
            'net_cost' => $this->net_cost,
            'approved_by' => $this->approved_by,
            'approved_by_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'attachment_path' => $this->attachment_path,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_by_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => AdjustmentLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
