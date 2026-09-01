<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Requests;

use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusSalesCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-credit-notes.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SalesCreditNote::STATUSES)],
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

            $note = app(SalesCreditNoteRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($note === null) {
                return;
            }

            $allowed = SalesCreditNote::STATUS_TRANSITIONS[$note->status] ?? [];

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
