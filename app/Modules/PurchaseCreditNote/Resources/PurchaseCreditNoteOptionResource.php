<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Nota de crédito a proveedor vista como opción de un select remoto
 * (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `PurchaseCreditNoteResource`: solo `value`,
 * `label` y lo que un pago necesita saber de la nota al elegirla —proveedor,
 * moneda y crédito disponible— sin una segunda ida al servidor.
 */
class PurchaseCreditNoteOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        $number = $this->supplier_document_number !== null
            ? " · {$this->supplier_document_number}"
            : '';

        return [
            'value' => $this->id,
            'label' => "{$this->code}{$number}",
            'meta' => [
                'code' => $this->code,
                'supplier_id' => $this->supplier_id,
                'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
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
