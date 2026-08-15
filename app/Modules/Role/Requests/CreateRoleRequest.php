<?php

declare(strict_types=1);

namespace App\Modules\Role\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'company_id' => ['nullable', 'string', 'uuid', 'exists:app_companies,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('app_roles')->where('company_id', session('current_company_id'))],
            'description' => ['nullable', 'string'],
            'permission_type' => ['required', 'string', 'in:all,custom'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ];
    }
}
