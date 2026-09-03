<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests\Api;

use App\Modules\Client\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

/**
 * El formulario del checkout es el registro: los mismos cuatro campos crean
 * la cuenta. La llave de la tienda ya autenticó la petición.
 */
class RegisterStoreCustomerApiRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'document_type' => ['nullable', 'string', 'in:'.implode(',', Client::DOCUMENT_TYPES), 'required_with:document_number'],
            'document_number' => ['nullable', 'string', 'regex:/^\d+$/', 'max:15', 'required_with:document_type'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no es válido.',
            'phone.required' => 'El teléfono es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'document_type.in' => 'La letra del RIF debe ser V, E, J, P, G o C.',
            'document_type.required_with' => 'Indica la letra del RIF.',
            'document_number.regex' => 'El número de RIF solo admite dígitos.',
            'document_number.required_with' => 'Indica el número del RIF.',
        ];
    }
}
