<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Requests;

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusPurchaseCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-credit-notes.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(PurchaseCreditNote::STATUSES)],
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
     * La nota avanza por un camino fijo (`draft` → `confirmed` → `completed`, o
     * `cancelled`), y una nota con crédito ya aplicado a facturas no se anula:
     * primero se revierten esas aplicaciones.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            $note = app(PurchaseCreditNoteRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($note === null) {
                return;
            }

            $allowed = PurchaseCreditNote::STATUS_TRANSITIONS[$note->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar la nota de «{$note->status}» a «{$status}».",
                );
            }

            if ($status === 'cancelled' && (float) $note->applied_amount > 0) {
                $validator->errors()->add(
                    'status',
                    'No se puede anular una nota con crédito ya aplicado a facturas.',
                );
            }
        });
    }
}
