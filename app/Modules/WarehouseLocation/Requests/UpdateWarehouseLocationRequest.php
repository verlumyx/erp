<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Requests;

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseLocationRequest extends FormRequest
{
    private ?WarehouseLocation $location = null;

    private bool $locationResolved = false;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('warehouse-locations.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');
        $location = $this->location();

        return [
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
                    ->where('warehouse_id', $location?->warehouse_id)
                    ->ignore($this->route('id')),
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
                $location = $this->location();

                if ($location === null) {
                    return;
                }

                if ($location->warehouse?->uses_locations !== 'yes') {
                    $validator->errors()->add(
                        'name',
                        'La bodega no gestiona ubicaciones: su ubicación por defecto no se puede editar.'
                    );

                    return;
                }

                $locationCode = (string) $this->input('location_code');

                if (strcasecmp($locationCode, Warehouse::DEFAULT_LOCATION_CODE) === 0
                    && $location->location_code !== Warehouse::DEFAULT_LOCATION_CODE) {
                    $validator->errors()->add(
                        'location_code',
                        'El código PRINCIPAL está reservado para la ubicación por defecto de la bodega.'
                    );
                }

                $parentId = $this->input('parent_id');

                if ($parentId === null) {
                    return;
                }

                if ($parentId === $location->id) {
                    $validator->errors()->add('parent_id', 'Una ubicación no puede ser su propia ubicación padre.');

                    return;
                }

                $parent = WarehouseLocation::find($parentId);

                if ($parent !== null && $parent->warehouse_id !== $location->warehouse_id) {
                    $validator->errors()->add('parent_id', 'La ubicación padre debe pertenecer a la misma bodega.');
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
            'name.required' => 'El nombre de la ubicación es obligatorio.',
            'location_code.required' => 'El código físico de la ubicación es obligatorio.',
            'location_code.unique' => 'Ya existe una ubicación con ese código en la bodega.',
            'type.in' => 'El tipo de ubicación no es válido.',
            'capacity.min' => 'La capacidad no puede ser negativa.',
        ];
    }

    private function location(): ?WarehouseLocation
    {
        if (! $this->locationResolved) {
            $this->location = WarehouseLocation::with('warehouse')
                ->where('company_id', session('current_company_id'))
                ->find($this->route('id'));

            $this->locationResolved = true;
        }

        return $this->location;
    }
}
