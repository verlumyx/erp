<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'type' => $this->type,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'related_type' => $this->related_type,
            'related_id' => $this->related_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'date' => $this->date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'period_from' => $this->period_from?->format('Y-m-d'),
            'period_to' => $this->period_to?->format('Y-m-d'),
            'description' => $this->description,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'receipt_url' => $this->receipt_url,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
