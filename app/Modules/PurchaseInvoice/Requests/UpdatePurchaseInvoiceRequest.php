<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Requests;

use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseInvoice\Requests\Concerns\ValidatesPurchaseInvoicePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePurchaseInvoiceRequest extends FormRequest
{
    use ValidatesPurchaseInvoicePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-invoices.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->purchaseInvoiceRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->purchaseInvoiceMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validatePurchaseInvoiceInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmada ya generó la cuenta por pagar y
     * consumió saldo de la orden: se corrige anulándola y emitiendo otra.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $invoice = app(PurchaseInvoiceRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($invoice !== null && $invoice->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar una factura en borrador.',
            );
        }
    }
}
