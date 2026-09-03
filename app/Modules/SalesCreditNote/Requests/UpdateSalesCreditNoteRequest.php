<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Requests;

use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use App\Modules\SalesCreditNote\Requests\Concerns\ValidatesSalesCreditNotePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSalesCreditNoteRequest extends FormRequest
{
    use ValidatesSalesCreditNotePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-credit-notes.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->salesCreditNoteRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->salesCreditNoteMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateSalesCreditNoteInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmada ya bajó la cuenta por cobrar del
     * cliente y quemó su correlativo fiscal: se corrige anulándola y emitiendo
     * otra.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $note = app(SalesCreditNoteRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($note !== null && ! in_array($note->status, SalesCreditNote::EDITABLE_STATUSES, true)) {
            $validator->errors()->add(
                'status',
                'Solo se puede editar una nota de crédito en borrador.',
            );
        }
    }
}
