<?php

declare(strict_types=1);

namespace App\Modules\Role\Requests;

use App\Modules\Role\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'string', 'uuid', 'exists:app_companies,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('app_roles')->where('company_id', session('current_company_id'))->ignore($this->route('id'))],
            'description' => ['nullable', 'string'],
            'permission_type' => ['required', 'string', 'in:all,custom'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $role = Role::find($this->route('id'));

            if ($role?->isAdministrator()) {
                $validator->errors()->add('name', 'El rol Administrador no se puede editar.');
            }
        });
    }
}
