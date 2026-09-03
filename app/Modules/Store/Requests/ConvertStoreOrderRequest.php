<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests;

use App\Modules\Client\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class ConvertStoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('store-orders.convert') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'uuid'],
            'create_client' => ['nullable', 'string', 'in:yes,no'],
            'document_type' => ['nullable', 'string', 'in:'.implode(',', Client::DOCUMENT_TYPES)],
            'document_number' => ['nullable', 'string', 'regex:/^\d+$/', 'max:15'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.uuid' => 'El cliente no es válido.',
            'create_client.in' => 'Indica si se crea el cliente con los datos del comprador.',
            'document_type.in' => 'La letra del RIF debe ser V, E, J, P, G o C.',
            'document_number.regex' => 'El número de RIF solo admite dígitos.',
            'document_number.max' => 'El número de RIF admite hasta 15 dígitos.',
        ];
    }
}
