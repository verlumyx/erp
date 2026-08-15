<?php

declare(strict_types=1);

namespace App\Modules\Menu\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusMenuRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
