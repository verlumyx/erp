<?php

declare(strict_types=1);

namespace App\Modules\Sale\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representa una fila del pivote sale_profiles, enriquecida con los datos del
 * profile y su account para que el detalle de la venta los muestre sin más requests.
 *
 * @mixin \App\Modules\Sale\Models\SaleProfile
 */
class SaleProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->profile;
        $account = $profile?->account;

        return [
            'id' => $this->id,
            'profile_id' => $this->profile_id,
            'number' => $profile?->number,
            'profile_status' => $profile?->status,
            'account' => $account === null ? null : [
                'id' => $account->id,
                'code' => $account->code,
                'email' => $account->email,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
