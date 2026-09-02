<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Requests;

use App\Modules\Transfer\Models\Transfer;
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
     * El traslado avanza por un camino fijo (`draft` → `confirmed` →
     * `completed`, o `cancelled`), pero cerrarlo no es una decisión de esta
     * pantalla: lo cierra la entrada del destino al confirmarse, que es cuando
     * la mercancía llegó de verdad.
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

            if ($status === 'completed') {
                $validator->errors()->add(
                    'status',
                    'El traslado se cierra solo cuando se confirma la entrada en la bodega de destino.',
                );
            }
        });
    }

}
