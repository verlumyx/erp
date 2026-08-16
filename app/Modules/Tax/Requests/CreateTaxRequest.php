<?php

declare(strict_types=1);

namespace App\Modules\Tax\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('taxes.create') ?? false;
    }

    /**
     * `withholding_percentage` solo se exige cuando hay retención; si no la hay
     * el comando lo fuerza a 0 sin importar lo que llegue.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('app_taxes', 'name')->where('company_id', session('current_company_id')),
            ],
            'description' => ['nullable', 'string'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'has_withholding' => ['required', 'string', 'in:yes,no'],
            'withholding_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                Rule::requiredIf(fn (): bool => $this->input('has_withholding') === 'yes'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del impuesto es obligatorio.',
            'name.unique' => 'Ya existe un impuesto con ese nombre.',
            'percentage.required' => 'El porcentaje del impuesto es obligatorio.',
            'percentage.numeric' => 'El porcentaje del impuesto debe ser numérico.',
            'percentage.min' => 'El porcentaje del impuesto no puede ser negativo.',
            'percentage.max' => 'El porcentaje del impuesto no puede ser mayor a 100.',
            'has_withholding.required' => 'Indica si el impuesto practica retención.',
            'has_withholding.in' => 'El valor de retención no es válido.',
            'withholding_percentage.required' => 'El porcentaje de retención es obligatorio cuando hay retención.',
            'withholding_percentage.numeric' => 'El porcentaje de retención debe ser numérico.',
            'withholding_percentage.min' => 'El porcentaje de retención no puede ser negativo.',
            'withholding_percentage.max' => 'El porcentaje de retención no puede ser mayor a 100.',
        ];
    }
}
