<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('price-lists.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('app_price_lists', 'name')
                    ->where('company_id', session('current_company_id'))
                    ->ignore($this->route('id')),
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
