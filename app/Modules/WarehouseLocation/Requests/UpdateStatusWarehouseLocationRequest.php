<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Requests;

use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('warehouse-locations.update-status') ?? false;
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

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('status') !== 'inactive') {
                    return;
                }

                $location = WarehouseLocation::with('warehouse')
                    ->where('company_id', session('current_company_id'))
                    ->find($this->route('id'));

                if ($location === null) {
                    return;
                }

                if ($location->warehouse?->uses_locations !== 'yes') {
                    $validator->errors()->add(
                        'status',
                        'La bodega no gestiona ubicaciones: su ubicación por defecto no se puede desactivar.'
                    );

                    return;
                }

                if ($location->is_default === 'yes') {
                    $validator->errors()->add(
                        'status',
                        'La ubicación por defecto de la bodega no se puede desactivar.'
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
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido.',
        ];
    }
}
