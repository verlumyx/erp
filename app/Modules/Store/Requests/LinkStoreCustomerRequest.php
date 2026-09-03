<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkStoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('store-customers.link') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Elige el cliente a vincular.',
            'client_id.uuid' => 'El cliente no es válido.',
        ];
    }
}
