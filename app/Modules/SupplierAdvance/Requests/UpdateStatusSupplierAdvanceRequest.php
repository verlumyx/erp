<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Requests;

use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusSupplierAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-advances.update-status') ?? false;
    }

    /**
     * La pantalla solo aprueba o anula: `confirmed` y la vuelta a `draft` los
     * decide el pago espejo, y `partial` / `completed` las aplicaciones a
     * facturas.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SupplierAdvance::REQUESTABLE_STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.in' => 'Desde aquí un anticipo solo se aprueba o se anula.',
        ];
    }

    /**
     * El anticipo avanza por un camino fijo (`draft` → `pending_confirmation`
     * → `confirmed` → `partial` → `completed`) y un anticipo con crédito ya
     * aplicado a facturas no se anula: primero se revierten las aplicaciones.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            $advance = app(SupplierAdvanceRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($advance === null) {
                return;
            }

            $allowed = SupplierAdvance::STATUS_TRANSITIONS[$advance->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el anticipo de «{$advance->status}» a «{$status}».",
                );
            }

            if ($status === 'cancelled' && (float) $advance->applied_amount > 0) {
                $validator->errors()->add(
                    'status',
                    'No se puede anular un anticipo con crédito ya aplicado a facturas.',
                );
            }
        });
    }
}
