<?php

declare(strict_types=1);

namespace App\Modules\Company\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_system_owner ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:app_companies,name,'.$this->route('id')],
            'description' => ['nullable', 'string'],
        ];
    }
}
