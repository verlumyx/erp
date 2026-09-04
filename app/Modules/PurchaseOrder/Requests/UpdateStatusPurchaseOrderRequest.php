<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Requests;

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-orders.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(PurchaseOrder::STATUSES)],
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
     * Por aquí solo pasan las dos decisiones del usuario —confirmar y anular— y
     * anular exige siempre un motivo. `partial` y `completed` los escribe el
     * avance de los documentos que cumplen la orden, nunca la pantalla, y por
     * eso no están en `STATUS_TRANSITIONS`.
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

            $order = app(PurchaseOrderRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($order === null) {
                return;
            }

            $allowed = PurchaseOrder::STATUS_TRANSITIONS[$order->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar la orden de «{$order->status}» a «{$status}».",
                );
            }
        });
    }
}
