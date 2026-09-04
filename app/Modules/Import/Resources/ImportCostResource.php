<?php

declare(strict_types=1);

namespace App\Modules\Import\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportCostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'import_id' => $this->import_id,
            'line_number' => $this->line_number,
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            /** El papel que respalda el cobro, para que la pantalla lo nombre. */
            'sourceable_code' => $this->whenLoaded('sourceable', fn () => $this->sourceable?->code),
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'concept' => $this->concept,
            'description' => $this->description,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'amount' => $this->amount,
            /** Lo que de verdad entra al reparto, ya en la moneda del expediente. */
            'converted_amount' => $this->converted_amount,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
