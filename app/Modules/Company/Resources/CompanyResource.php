<?php

declare(strict_types=1);

namespace App\Modules\Company\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rif' => $this->rif,
            'status' => $this->status,
            'address' => $this->address,
            'fiscal_address' => $this->fiscal_address,
            'phone' => $this->phone,
            'taxpayer_type' => $this->taxpayer_type,
            'description' => $this->description,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
