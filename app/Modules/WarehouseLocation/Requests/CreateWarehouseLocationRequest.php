<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Requests;

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('warehouse-locations.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'id' => ['required', 'uuid'],
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('uses_locations', 'yes'),
            ],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouse_locations', 'id')->where('company_id', $companyId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'location_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('app_warehouse_locations', 'location_code')
                    ->where('warehouse_id', $this->input('warehouse_id')),
            ],
            'type' => ['required', 'string', 'in:zone,aisle,shelf,bin'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['nullable', 'string', 'in:yes,no'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (strcasecmp((string) $this->input('location_code'), Warehouse::DEFAULT_LOCATION_CODE) === 0) {
                    $validator->errors()->add(
                        'location_code',
                        'El código PRINCIPAL está reservado para la ubicación por defecto de la bodega.'
                    );
                }

                $parentId = $this->input('parent_id');

                if ($parentId === null) {
                    return;
                }

                $parent = WarehouseLocation::find($parentId);

                if ($parent !== null && $parent->warehouse_id !== $this->input('warehouse_id')) {
                    $validator->errors()->add(
                        'parent_id',
                        'La ubicación padre debe pertenecer a la misma bodega.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'La bodega es obligatoria.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe o no gestiona ubicaciones.',
            'name.required' => 'El nombre de la ubicación es obligatorio.',
            'location_code.required' => 'El código físico de la ubicación es obligatorio.',
            'location_code.unique' => 'Ya existe una ubicación con ese código en la bodega.',
            'type.in' => 'El tipo de ubicación no es válido.',
            'capacity.min' => 'La capacidad no puede ser negativa.',
        ];
    }
}
