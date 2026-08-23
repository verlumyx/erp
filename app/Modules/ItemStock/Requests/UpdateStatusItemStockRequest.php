<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Requests;

use App\Modules\ItemStock\Models\ItemStock;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusItemStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('item-stocks.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('status') !== 'inactive') {
                    return;
                }

                $stock = ItemStock::query()
                    ->where('company_id', session('current_company_id'))
                    ->find($this->route('id'));

                if ($stock === null) {
                    return;
                }

                /*
                 * Solo se retira de la vista un saldo que ya no existe. Un saldo
                 * con existencia, reserva o mercancía en camino tiene que seguir
                 * cuadrando contra el kardex.
                 */
                if ((float) $stock->quantity !== 0.0
                    || (float) $stock->reserved_quantity !== 0.0
                    || (float) $stock->incoming_quantity !== 0.0) {
                    $validator->errors()->add(
                        'status',
                        'Solo se desactiva un saldo en cero, sin reservas ni mercancía en camino.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido.',
        ];
    }
}
