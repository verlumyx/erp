<?php

declare(strict_types=1);

namespace App\Modules\Import\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recepción vista como opción del select del expediente.
 *
 * Es deliberadamente más pobre que `EntryResource`: solo `value`, `label` y lo
 * que la pantalla necesita para enseñar qué está eligiendo —proveedor, bodega,
 * fecha y valor ingresado— sin una segunda ida al servidor.
 */
class ImportEntryOptionResource extends JsonResource
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
                'supplier_name' => $this->supplier?->name,
                'warehouse_id' => $this->warehouse_id,
                'warehouse_name' => $this->warehouse?->name,
                'entry_date' => $this->entry_date?->format('Y-m-d'),
                'currency' => $this->currency,
                'total_cost' => $this->total_cost,
                'status' => $this->status,
            ],
        ];
    }
}
