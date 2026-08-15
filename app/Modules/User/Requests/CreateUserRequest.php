<?php

declare(strict_types=1);

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isExistingUser = $this->filled('existing_user_id');

        return [
            'id' => ['required', 'uuid'],
            'existing_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'name' => $isExistingUser ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => $isExistingUser ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'string', 'exists:app_roles,id'],
        ];
    }
}
