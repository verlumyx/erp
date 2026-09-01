<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Requests;

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('adjustments.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Adjustment::STATUSES)],
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
     * El ajuste avanza por un camino fijo (`draft` → `pending_approval` →
     * `confirmed` → `completed`, o `cancelled`), anular exige siempre un motivo
     * y confirmar exige el permiso de aprobación: mover existencia sin una
     * operación comercial detrás no es un paso más del documento.
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

            if ($status === 'confirmed' && ! ($this->user()?->hasPermission('adjustments.approve') ?? false)) {
                $validator->errors()->add(
                    'status',
                    'No tienes permiso para aprobar ajustes de inventario.',
                );
            }

            $adjustment = app(AdjustmentRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($adjustment === null) {
                return;
            }

            $allowed = Adjustment::STATUS_TRANSITIONS[$adjustment->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el ajuste de «{$adjustment->status}» a «{$status}».",
                );
            }
        });
    }
}
