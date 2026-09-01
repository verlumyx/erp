<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Requests;

use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transfers.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Transfer::STATUSES)],
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
     * El traslado avanza por un camino fijo (`draft` → `confirmed` → `partial`
     * → `completed`, o `cancelled`), y no se cierra un viaje del que todavía no
     * se sabe si llegó.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            $transfer = app(TransferRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($transfer === null) {
                return;
            }

            $allowed = Transfer::STATUS_TRANSITIONS[$transfer->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el traslado de «{$transfer->status}» a «{$status}».",
                );

                return;
            }

            if ($status !== 'completed') {
                return;
            }

            if (! $transfer->isReceiptSettled()) {
                $validator->errors()->add(
                    'status',
                    'Registra primero la recepción en la bodega de destino.',
                );

                return;
            }

            $this->validateNoUnjustifiedDifference($validator, $transfer);
        });
    }

    /**
     * Una diferencia entre lo que salió y lo que llegó exige un Ajuste que la
     * justifique antes de cerrar el traslado. El ajuste no apunta al traslado
     * —no hay columna que los ate—, así que el sistema no puede comprobar solo
     * que ya se hizo: cerrar con faltante es una decisión explícita y se
     * protege con su propio permiso, en lugar de dejar el documento atascado
     * para siempre.
     */
    private function validateNoUnjustifiedDifference(Validator $validator, Transfer $transfer): void
    {
        $difference = TransferLine::query()
            ->where('transfer_id', $transfer->id)
            ->where('status', 'active')
            ->sum('difference_quantity');

        if ((float) $difference == 0.0) {
            return;
        }

        if ($this->user()?->hasPermission('transfers.close-with-difference') ?? false) {
            return;
        }

        $validator->errors()->add(
            'status',
            'El traslado llegó con faltante: justifícalo con un ajuste antes de cerrarlo.',
        );
    }
}
