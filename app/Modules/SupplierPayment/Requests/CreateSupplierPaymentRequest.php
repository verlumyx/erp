<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Requests;

use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Requests\Concerns\ValidatesSupplierPaymentPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateSupplierPaymentRequest extends FormRequest
{
    use ValidatesSupplierPaymentPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-payments.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            /**
             * El origen se elige al crear y se congela. `advance` no está aquí:
             * ese pago lo genera la aprobación de un anticipo.
             */
            'origin_type' => ['required', 'string', Rule::in(SupplierPayment::CREATABLE_ORIGIN_TYPES)],
            'origin_id' => ['nullable', 'uuid'],
            ...$this->supplierPaymentRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'origin_type.required' => 'Indica desde dónde se aplica el pago.',
            'origin_type.in' => 'Un pago solo se inicia desde un proveedor o desde una factura.',
            ...$this->supplierPaymentMessages(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateSupplierPaymentInvariants($validator));
    }
}
