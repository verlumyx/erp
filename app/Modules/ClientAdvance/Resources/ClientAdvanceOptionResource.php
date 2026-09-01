<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Anticipo visto como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `ClientAdvanceResource`: solo `value`,
 * `label` y lo que un cobro necesita saber del anticipo al elegirlo —cliente,
 * moneda y crédito disponible— sin una segunda ida al servidor.
 */
class ClientAdvanceOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        $reference = $this->reference !== null ? " · {$this->reference}" : '';

        return [
            'value' => $this->id,
            'label' => "{$this->code}{$reference}",
            'meta' => [
                'code' => $this->code,
                'client_id' => $this->client_id,
                'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
                'advance_date' => $this->advance_date?->format('Y-m-d'),
                'currency' => $this->currency,
                /** Con la tasa que congeló sale el diferencial cambiario al aplicarlo. */
                'exchange_rate' => $this->exchange_rate,
                'amount' => $this->amount,
                'applied_amount' => $this->applied_amount,
                'balance' => $this->balance,
                'status' => $this->status,
            ],
        ];
    }
}
