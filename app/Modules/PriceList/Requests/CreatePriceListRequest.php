<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('price-lists.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('app_price_lists', 'name')->where('company_id', session('current_company_id')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la lista de precio es obligatorio.',
            'name.unique' => 'Ya existe una lista de precio con ese nombre en la empresa.',
        ];
    }
}
