<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Requests\Concerns;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Validation\Rule;

trait ValidatesVehiclePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function vehicleRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('app_vehicles', 'plate')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'vehicle_type' => ['required', 'string', Rule::in(array_keys(Vehicle::VEHICLE_TYPES))],
            'capacity_weight' => ['nullable', 'numeric', 'min:0'],
            'capacity_volume' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate.required' => 'La placa del vehículo es obligatoria.',
            'plate.unique' => 'Ya existe un camión registrado con esta placa en la empresa.',
            'name.required' => 'El nombre o descripción del vehículo es obligatorio.',
            'vehicle_type.required' => 'El tipo de vehículo es obligatorio.',
            'vehicle_type.in' => 'El tipo de vehículo seleccionado no es válido.',
            'capacity_weight.min' => 'La capacidad en peso no puede ser negativa.',
            'capacity_volume.min' => 'La capacidad en volumen no puede ser negativa.',
        ];
    }
}
