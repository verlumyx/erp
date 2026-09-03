<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * El carrito tal como lo manda la tienda: dirección, comentario y líneas
 * con `slug` y cantidad. Sin precios: los resuelve el ERP.
 */
class CreateStoreOrderApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_address_id' => ['nullable', 'uuid'],
            'delivery_address' => ['required_without:client_address_id', 'nullable', 'string', 'max:500'],
            'delivery_city' => ['nullable', 'string', 'max:100'],
            'delivery_state' => ['nullable', 'string', 'max:100'],
            'buyer_notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.slug' => ['required', 'string', 'max:160'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_address_id.uuid' => 'La dirección elegida no es válida.',
            'delivery_address.required_without' => 'Indica una dirección de entrega.',
            'delivery_address.max' => 'La dirección admite hasta 500 caracteres.',
            'lines.required' => 'El carrito está vacío.',
            'lines.min' => 'El carrito está vacío.',
            'lines.*.slug.required' => 'Falta el producto de la línea.',
            'lines.*.quantity.required' => 'Indica la cantidad.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
        ];
    }
}
