<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Requests;

use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusPurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-returns.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(PurchaseReturn::STATUSES)],
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
     * La devolución avanza por un camino fijo (`draft` → `confirmed` →
     * `completed`, o `cancelled`), y una que ya está acreditada con su nota de
     * crédito no se anula: primero se anula la nota.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            $return = app(PurchaseReturnRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($return === null) {
                return;
            }

            $allowed = PurchaseReturn::STATUS_TRANSITIONS[$return->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar la devolución de «{$return->status}» a «{$status}».",
                );
            }

            if ($status === 'cancelled' && filled($return->credit_note_id)) {
                $validator->errors()->add(
                    'status',
                    'No se puede anular una devolución ya acreditada con una nota de crédito.',
                );
            }
        });
    }
}
