<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\ManualTransaction\Models\ManualTransaction
 */
class ManualTransactionResource extends JsonResource
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
            'date' => $this->date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'currency' => $this->currency,
            'description' => $this->description,
            'notes' => $this->notes,
            'total' => $this->total,
            'status' => $this->status,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'is_pending' => $this->isPending(),
            'can_be_approved' => $this->canBeApproved(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'recorded_by' => $this->recorded_by,
            'recorded_by_user' => $this->whenLoaded('recordedBy', fn () => $this->recordedBy === null ? null : [
                'id' => $this->recordedBy->id,
                'name' => $this->recordedBy->name,
            ]),
            'lines' => $this->whenLoaded('lines', fn () => ManualTransactionLineResource::collection($this->lines)->resolve()),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
