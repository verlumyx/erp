<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-types.update') ?? false;
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
                Rule::unique('app_supplier_types', 'name')
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
            'name.required' => 'El nombre del tipo de proveedor es obligatorio.',
            'name.unique' => 'Ya existe un tipo de proveedor con ese nombre.',
        ];
    }
}
