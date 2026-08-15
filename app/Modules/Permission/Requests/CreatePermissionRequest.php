<?php

declare(strict_types=1);

namespace App\Modules\Permission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'module_id' => ['required', 'string', 'uuid'],
            'action' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ];
    }
}
