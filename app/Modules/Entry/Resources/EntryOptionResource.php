<?php

declare(strict_types=1);

namespace App\Modules\Entry\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Entrada vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `EntryResource`: solo `value`, `label` y lo
 * que un documento que la referencia necesita saber al elegirla —proveedor,
 * bodega, moneda y valor ingresado— sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `EntryOptionSearchService`.
 */
class EntryOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        $document = $this->supplier_document !== null ? " · {$this->supplier_document}" : '';

        return [
            'value' => $this->id,
            'label' => "{$this->code}{$document}",
            'meta' => [
                'code' => $this->code,
                'supplier_id' => $this->supplier_id,
                'supplier_name' => $this->supplier?->name,
                'warehouse_id' => $this->warehouse_id,
                'entry_date' => $this->entry_date?->format('Y-m-d'),
                'entry_type' => $this->entry_type,
                'currency' => $this->currency,
                /** Con la tasa que congeló se reexpresa lo ingresado. */
                'exchange_rate' => $this->exchange_rate,
                'total_cost' => $this->total_cost,
                'is_invoiced' => $this->is_invoiced,
                'status' => $this->status,
            ],
        ];
    }
}
