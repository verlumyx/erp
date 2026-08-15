<?php

declare(strict_types=1);

namespace App\Modules\Account\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountRenewalResource extends JsonResource
{
    /**
     * Forma la respuesta de un movimiento de renovación del histórico de la account.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
