<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Requests;

use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-invoices.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SalesInvoice::STATUSES)],
            'cancellation_reason' => [
                Rule::requiredIf(fn (): bool => $this->input('status') === 'cancelled'),
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Indica el nuevo estado de la factura.',
            'status.in' => 'El estado indicado no es válido para una factura de venta.',
            'cancellation_reason.required' => 'Indica el motivo de la anulación.',
        ];
    }

    /**
     * El ciclo es dirigido: draft → confirmed → completed, con `cancelled`
     * como salida. Una factura cobrada o anulada ya es final.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invoice = app(SalesInvoiceRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($invoice === null) {
                return;
            }

            $status = (string) $this->input('status');
            $allowed = SalesInvoice::STATUS_TRANSITIONS[$invoice->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    'No se puede pasar una factura de "'.$invoice->status.'" a "'.$status.'".',
                );
            }

            if ($status === 'confirmed' && $invoice->lines->where('status', 'active')->isEmpty()) {
                $validator->errors()->add(
                    'status',
                    'No se puede emitir una factura sin líneas activas.',
                );
            }

            /** Anular con cobros aplicados descuadraría la cuenta por cobrar. */
            if ($status === 'cancelled' && (float) $invoice->paid_amount > 0) {
                $validator->errors()->add(
                    'status',
                    'No se puede anular una factura con cobros aplicados: emite una nota de crédito.',
                );
            }
        });
    }
}
