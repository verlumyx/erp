<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('measurement-units.update') ?? false;
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
                'max:100',
                Rule::unique('app_measurement_units', 'name')
                    ->where('company_id', session('current_company_id'))
                    ->ignore($this->route('id')),
            ],
            'abbreviation' => [
                'required',
                'string',
                'max:10',
                Rule::unique('app_measurement_units', 'abbreviation')
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
            'name.required' => 'El nombre de la unidad es obligatorio.',
            'name.unique' => 'Ya existe una unidad de medida con ese nombre.',
            'abbreviation.required' => 'El símbolo es obligatorio.',
            'abbreviation.unique' => 'Ya existe una unidad de medida con ese símbolo.',
        ];
    }
}
