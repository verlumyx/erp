<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Requests;

use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-payments.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SupplierPayment::STATUSES)],
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
     * El pago avanza por un camino fijo (`draft` → `confirmed` → `completed`,
     * o `cancelled`) y anular exige siempre un motivo.
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

            $payment = app(SupplierPaymentRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($payment === null) {
                return;
            }

            $allowed = SupplierPayment::STATUS_TRANSITIONS[$payment->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el pago de «{$payment->status}» a «{$status}».",
                );
            }
        });
    }
}
