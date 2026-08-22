<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Requests;

use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\SalesInvoice\Requests\Concerns\ValidatesSalesInvoicePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSalesInvoiceRequest extends FormRequest
{
    use ValidatesSalesInvoicePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-invoices.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->salesInvoiceRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->salesInvoiceMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateSalesInvoiceInvariants($validator);
            $this->validateInvoiceIsEditable($validator);
        });
    }

    /**
     * Una factura confirmada ya quemó su correlativo fiscal y cargó la cuenta
     * por cobrar: se anula y se emite otra, o se corrige con nota de crédito.
     */
    private function validateInvoiceIsEditable(Validator $validator): void
    {
        $invoice = app(SalesInvoiceRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($invoice === null || in_array($invoice->status, SalesInvoice::EDITABLE_STATUSES, true)) {
            return;
        }

        $validator->errors()->add(
            'status',
            'Solo se puede editar una factura en borrador.',
        );
    }
}
