<?php

declare(strict_types=1);

namespace App\Modules\Company\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyMenusRequest extends FormRequest
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
            'disabled_menus' => ['present', 'array'],
            'disabled_menus.*' => ['string', 'uuid', 'exists:app_menus,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'disabled_menus.present' => 'Debe indicar la lista de menús deshabilitados.',
            'disabled_menus.array' => 'La lista de menús deshabilitados no es válida.',
            'disabled_menus.*.uuid' => 'Uno de los menús indicados no es válido.',
            'disabled_menus.*.exists' => 'Uno de los menús indicados no existe.',
        ];
    }
}
