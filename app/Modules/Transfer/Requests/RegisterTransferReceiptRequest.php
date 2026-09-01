<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTransferReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transfers.receive') ?? false;
    }

    /**
     * Solo se captura lo que llegó a la bodega de destino. Que las cantidades
     * no superen lo que salió, que las líneas sean de este traslado y que la
     * entrada se escriba en el kardex lo comprueba `TransferReceiptService`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'received_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],

            /** Vacío da por llegado todo: lo normal es que el viaje salga bien. */
            'lines' => ['nullable', 'array'],
            'lines.*.id' => ['required', 'uuid'],
            'lines.*.received_quantity' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.*.id.required' => 'Cada línea de la recepción debe decir cuál es.',
            'lines.*.received_quantity.required' => 'Indica cuánto llegó a la bodega de destino.',
            'lines.*.received_quantity.min' => 'La cantidad recibida no puede ser negativa.',
        ];
    }
}
