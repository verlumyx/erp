<?php

declare(strict_types=1);

namespace App\Modules\Refund\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Refund\Models\Refund
 */
class RefundResource extends JsonResource
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
            'sale_id' => $this->sale_id,
            'sale' => $this->whenLoaded('sale', fn () => [
                'id' => $this->sale->id,
                'code' => $this->sale->code,
                'status' => $this->sale->status,
                'price' => $this->sale->price,
            ]),
            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'code' => $this->client->code,
            ]),
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'requested_by_user' => $this->whenLoaded('requestedBy', fn () => [
                'id' => $this->requestedBy?->id,
                'name' => $this->requestedBy?->name,
            ]),
            'resolved_by' => $this->resolved_by,
            'resolved_by_user' => $this->whenLoaded('resolvedBy', fn () => [
                'id' => $this->resolvedBy?->id,
                'name' => $this->resolvedBy?->name,
            ]),
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'is_pending' => $this->isPending(),
            'transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => $transaction->amount,
                'date' => $transaction->date?->format('Y-m-d'),
                'description' => $transaction->description,
            ])->all()),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
