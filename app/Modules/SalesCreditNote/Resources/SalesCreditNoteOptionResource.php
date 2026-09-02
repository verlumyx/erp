<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Nota de crédito vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `SalesCreditNoteResource`: solo `value`,
 * `label` y lo que un cobro necesita saber de la nota al elegirla —cliente,
 * moneda y crédito disponible— sin una segunda ida al servidor.
 */
class SalesCreditNoteOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        $number = $this->note_number !== null ? " · {$this->note_number}" : '';

        return [
            'value' => $this->id,
            'label' => "{$this->code}{$number}",
            'meta' => [
                'code' => $this->code,
                'client_id' => $this->client_id,
                'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
                'note_date' => $this->note_date?->format('Y-m-d'),
                'currency' => $this->currency,
                /** Con la tasa que congeló sale el diferencial cambiario al aplicarla. */
                'exchange_rate' => $this->exchange_rate,
                'total' => $this->total,
                'applied_amount' => $this->applied_amount,
                'balance' => $this->balance,
                'status' => $this->status,
            ],
        ];
    }
}
