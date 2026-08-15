<?php

declare(strict_types=1);

namespace App\Modules\Permission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusPermissionRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
        ];
    }
}
