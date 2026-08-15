<?php

declare(strict_types=1);

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.update-status') ?? false;
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
}
