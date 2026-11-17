<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadPlanDispatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dispatch = $this->dispatch;

        return [
            'id' => $this->id,
            'dispatch_id' => $this->dispatch_id,
            'sequence' => $this->sequence,
            'status' => $this->status,
            'dispatch_code' => $dispatch?->code,
            'dispatch_date' => $dispatch?->dispatch_date?->toDateString(),
            'recipient_name' => $dispatch?->recipient?->name ?? '—',
            'recipient_code' => $dispatch?->recipient?->code,
            'address' => $dispatch?->clientAddress?->address ?? $dispatch?->clientAddress?->name ?? '—',
            'order_code' => $this->order_code ?? ($dispatch?->sourceable_type === 'sales_order' ? $dispatch->sourceable?->code : null),
            'invoice_code' => $this->invoice_code ?? ($dispatch?->sourceable_type === 'sales_invoice' ? $dispatch->sourceable?->code : null),
            'total_quantity' => (float) ($dispatch?->total_quantity ?? 0),
            'total_weight' => (float) ($dispatch?->total_weight ?? 0),
            'total_volume' => (float) ($dispatch?->total_volume ?? 0),
            'dispatch_status' => $dispatch?->status,
        ];
    }
}
