<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\Models\Dispatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDispatchDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dispatches.deliver') ?? false;
    }

    /**
     * Solo se capturan el resultado del viaje y lo que recibió el cliente. Que
     * las cantidades cuadren con el resultado, que las líneas sean de este
     * despacho y que el reingreso se escriba lo comprueba
     * `DispatchDeliveryService`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delivery_status' => [
                'required',
                'string',
                Rule::in(Dispatch::SETTLED_DELIVERY_STATUSES),
            ],
            'delivery_date' => ['nullable', 'date'],
            'received_by_name' => ['nullable', 'string', 'max:150'],
            'received_by_document' => ['nullable', 'string', 'max:30'],
            'signature_path' => ['nullable', 'string', 'max:500'],
            'evidence_path' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],

            /** Vacío da por entregado todo: lo normal es que el viaje salga bien. */
            'lines' => ['nullable', 'array'],
            'lines.*.id' => ['required', 'uuid'],
            'lines.*.delivered_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.returned_quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivery_status.required' => 'Indica cómo terminó la entrega.',
            'delivery_status.in' => 'Ese resultado de entrega no existe.',
            'lines.*.id.required' => 'Cada línea de la entrega debe decir cuál es.',
            'lines.*.delivered_quantity.required' => 'Indica cuánto recibió el cliente.',
            'lines.*.delivered_quantity.min' => 'La cantidad entregada no puede ser negativa.',
            'lines.*.returned_quantity.min' => 'La cantidad devuelta no puede ser negativa.',
        ];
    }
}
