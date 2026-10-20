<?php

declare(strict_types=1);

namespace App\Modules\Pos\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePosRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pos-registers.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('id') || ! $this->filled('id')) {
            $this->merge(['id' => (string) \Illuminate\Support\Str::uuid7()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'id' => ['required', 'uuid'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('app_pos_registers', 'name')->where('company_id', $companyId),
            ],
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'price_list_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_price_lists', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'default_client_id' => [
                'required',
                'uuid',
                Rule::exists('app_clients', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'receipt_printer_name' => ['nullable', 'string', 'max:100'],
            'invoice_series' => ['nullable', 'string', 'max:20'],
            'allow_price_override' => ['nullable', 'string', 'in:yes,no'],
            'allow_custom_discounts' => ['nullable', 'string', 'in:yes,no'],
            'max_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la caja es obligatorio.',
            'name.unique' => 'Ya existe una caja con ese nombre en la empresa.',
            'warehouse_id.required' => 'La bodega de descarga es obligatoria.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe o está inactiva.',
            'default_client_id.required' => 'El cliente por defecto es obligatorio.',
            'default_client_id.exists' => 'El cliente por defecto seleccionado no existe o está inactivo.',
            'price_list_id.exists' => 'La lista de precios seleccionada no existe o está inactiva.',
        ];
    }
}
