<?php

declare(strict_types=1);

namespace App\Modules\Role\Requests;

use App\Modules\Role\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStatusRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $role = Role::find($this->route('id'));

            if ($role?->isAdministrator()) {
                $validator->errors()->add('status', 'El rol Administrador no se puede modificar.');
            }
        });
    }
}
