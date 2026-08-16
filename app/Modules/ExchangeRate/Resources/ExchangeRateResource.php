<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
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
            'currency' => $this->currency,
            'rate_date' => $this->rate_date?->format('Y-m-d'),
            'rate' => (string) $this->rate,
            'type' => $this->type,
            'source' => $this->source,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
