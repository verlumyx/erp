<?php

declare(strict_types=1);

namespace App\Modules\Service\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('services.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('app_services', 'name')->where('company_id', session('current_company_id')),
            ],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'max_profiles' => ['required', 'integer', 'min:1'],
        ];
    }
}
