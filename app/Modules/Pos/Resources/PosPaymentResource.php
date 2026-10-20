<?php

declare(strict_types=1);

namespace App\Modules\Pos\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pos_sale_id' => $this->pos_sale_id,
            'payment_method' => $this->payment_method,
            'currency' => $this->currency,
            'exchange_rate' => (float) $this->exchange_rate,
            'amount_tendered' => (float) $this->amount_tendered,
            'amount_applied' => (float) $this->amount_applied,
            'amount_in_document_currency' => (float) $this->amount_in_document_currency,
            'reference' => $this->reference,
            'card_last_digits' => $this->card_last_digits,
            'status' => $this->status,
        ];
    }
}
