<?php

declare(strict_types=1);

namespace App\Modules\Menu\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMenuRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'permission' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:255'],
        ];
    }
}
