<?php

declare(strict_types=1);

namespace App\Modules\Client\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('clients.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('app_clients', 'email')->where('company_id', session('current_company_id')),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
