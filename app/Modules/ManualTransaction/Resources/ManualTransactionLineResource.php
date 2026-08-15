<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\ManualTransaction\Models\ManualTransactionLine
 */
class ManualTransactionLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'amount' => $this->amount,
            'description' => $this->description,
        ];
    }
}
