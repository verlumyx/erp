<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Requests;

use App\Modules\Supplier\Requests\Concerns\ValidatesSupplierPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSupplierRequest extends FormRequest
{
    use ValidatesSupplierPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('suppliers.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'document_number' => [
                'required',
                'string',
                'max:15',
                'regex:/^[0-9]+$/',
                Rule::unique('app_suppliers', 'document_number')
                    ->where('company_id', $companyId)
                    ->where('document_type', strtoupper((string) $this->input('document_type')))
                    ->ignore($this->route('id')),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('app_suppliers', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            ...$this->supplierRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->supplierMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateSupplierInvariants($validator));
    }
}
