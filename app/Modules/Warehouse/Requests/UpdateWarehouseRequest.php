<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('warehouses.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('app_warehouses', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            'type' => ['required', 'string', 'in:main,branch,transit,quarantine,virtual'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'responsible_user_id' => [
                'nullable',
                'uuid',
                Rule::exists('user_company', 'user_id')->where('company_id', $companyId),
            ],
            'is_default' => ['nullable', 'string', 'in:yes,no'],
            'allows_negative_stock' => ['nullable', 'string', 'in:yes,no'],
            'uses_locations' => ['nullable', 'string', 'in:yes,no'],
            'is_sales_available' => ['nullable', 'string', 'in:yes,no'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la bodega es obligatorio.',
            'name.unique' => 'Ya existe una bodega con ese nombre en la empresa.',
            'type.in' => 'El tipo de bodega no es válido.',
            'responsible_user_id.exists' => 'El encargado seleccionado no pertenece a la empresa.',
        ];
    }
}
