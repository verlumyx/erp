<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Requests;

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusPurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-invoices.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(PurchaseInvoice::STATUSES)],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.in' => 'El estado indicado no existe.',
        ];
    }

    /**
     * El documento avanza por un camino fijo (`draft` → `confirmed` →
     * `completed`, o `cancelled`), anular exige siempre un motivo y una factura
     * con pagos aplicados no se anula: primero se revierten los pagos.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            if ($status === 'cancelled' && trim((string) $this->input('cancellation_reason')) === '') {
                $validator->errors()->add(
                    'cancellation_reason',
                    'El motivo de anulación es obligatorio.',
                );
            }

            $invoice = app(PurchaseInvoiceRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($invoice === null) {
                return;
            }

            $allowed = PurchaseInvoice::STATUS_TRANSITIONS[$invoice->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar la factura de «{$invoice->status}» a «{$status}».",
                );
            }

            if ($status === 'cancelled' && (float) $invoice->paid_amount > 0) {
                $validator->errors()->add(
                    'status',
                    'No se puede anular una factura con pagos aplicados.',
                );
            }
        });
    }
}
