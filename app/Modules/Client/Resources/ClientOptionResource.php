<?php

declare(strict_types=1);

namespace App\Modules\Client\Resources;

use App\Modules\Client\Models\ClientAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cliente visto como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `ClientResource`: solo `value`, `label` y lo
 * que el pedido de venta copia en su cabecera al elegirlo. Las direcciones van
 * porque de ellas sale la dirección de entrega, que se elige en la misma
 * pantalla y sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `ClientOptionSearchService`.
 */
class ClientOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => $this->code ? "{$this->code} — {$this->name}" : $this->name,
            'meta' => [
                'code' => $this->code,
                'name' => $this->name,
                'price_list_id' => $this->price_list_id,
                'salesperson_id' => $this->salesperson_id,
                'payment_term_days' => (int) $this->payment_term_days,
                'discount_percent' => (string) $this->discount_percent,
                'credit_blocked' => $this->credit_blocked,
                /**
                 * Los dos indicadores que la pantalla de cobros muestra junto
                 * al cliente: lo que nos debe y el crédito que ya tiene a
                 * favor.
                 */
                'current_balance' => (string) $this->current_balance,
                'advance_balance' => (string) $this->advance_balance,
                'status' => $this->status,
                'addresses' => $this->addresses
                    ->where('status', 'active')
                    ->map(fn (ClientAddress $address): array => [
                        'id' => $address->id,
                        'type' => $address->type,
                        'name' => $address->name,
                        'address' => $address->address,
                        'is_default' => $address->is_default,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
