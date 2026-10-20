<?php

declare(strict_types=1);

namespace App\Modules\Pos\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosRegisterResource extends JsonResource
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
            'name' => $this->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'price_list_id' => $this->price_list_id,
            'price_list_name' => $this->whenLoaded('priceList', fn () => $this->priceList?->name),
            'default_client_id' => $this->default_client_id,
            'default_client_name' => $this->whenLoaded('defaultClient', fn () => $this->defaultClient?->name),
            'default_client_label' => $this->whenLoaded('defaultClient', fn () => $this->defaultClient ? ($this->defaultClient->code ? "{$this->defaultClient->code} — {$this->defaultClient->name}" : $this->defaultClient->name) : null),
            'receipt_printer_name' => $this->receipt_printer_name,
            'invoice_series' => $this->invoice_series,
            'allow_price_override' => $this->allow_price_override,
            'allow_custom_discounts' => $this->allow_custom_discounts,
            'max_discount_percent' => (float) $this->max_discount_percent,
            'notes' => $this->notes,
            'status' => $this->status,
            'is_available' => $this->isAvailable(),
            'active_session' => $this->whenLoaded('activeSession', fn () => $this->activeSession ? [
                'id' => $this->activeSession->id,
                'code' => $this->activeSession->code,
                'opened_at' => $this->activeSession->opened_at?->format('Y-m-d H:i:s'),
                'user_name' => $this->activeSession->user?->name,
            ] : null),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
