<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Requests;

use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use App\Modules\PurchaseCreditNote\Requests\Concerns\ValidatesPurchaseCreditNotePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePurchaseCreditNoteRequest extends FormRequest
{
    use ValidatesPurchaseCreditNotePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-credit-notes.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->purchaseCreditNoteRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->purchaseCreditNoteMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validatePurchaseCreditNoteInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmada ya bajó el saldo del proveedor y, si
     * afecta inventario, movió el kardex: se corrige anulándola y emitiendo
     * otra.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $note = app(PurchaseCreditNoteRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($note !== null && $note->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar una nota de crédito en borrador.',
            );
        }
    }
}
