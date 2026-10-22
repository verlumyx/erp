<?php

declare(strict_types=1);

namespace App\Modules\Company\Requests;

use App\Modules\Company\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCompanyRequest extends FormRequest
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
            'id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255', 'unique:app_companies,name'],
            'rif' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'fiscal_address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'taxpayer_type' => ['nullable', 'string', Rule::in(Company::TAXPAYER_TYPES)],
            'description' => ['nullable', 'string'],
        ];
    }
}
