<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Requests;

use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use App\Modules\SupplierPayment\Requests\Concerns\ValidatesSupplierPaymentPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSupplierPaymentRequest extends FormRequest
{
    use ValidatesSupplierPaymentPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-payments.update') ?? false;
    }

    /**
     * El origen no está en las reglas a propósito: se congeló al crear el pago
     * y lo que llegue en el payload se ignora.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->supplierPaymentRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->supplierPaymentMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateSupplierPaymentInvariants($validator);
        });
    }

    /**
     * Solo se edita en borrador: confirmado el pago ya movió el saldo de las
     * facturas y del proveedor, y se corrige anulándolo y registrando otro.
     *
     * El pago espejo de un anticipo no se edita nunca: monto, moneda, tasa,
     * proveedor y forma de pago son propiedad del anticipo.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $payment = app(SupplierPaymentRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($payment === null) {
            return;
        }

        if ($payment->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un pago en borrador.',
            );
        }

        if ($payment->origin_type === 'advance') {
            $validator->errors()->add(
                'origin_type',
                'El pago de un anticipo se corrige desde el anticipo, no aquí.',
            );
        }
    }
}
