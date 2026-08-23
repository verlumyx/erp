<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Requests;

use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Modules\PurchaseReturn\Requests\Concerns\ValidatesPurchaseReturnPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePurchaseReturnRequest extends FormRequest
{
    use ValidatesPurchaseReturnPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-returns.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->purchaseReturnRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->purchaseReturnMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validatePurchaseReturnInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmada, la mercancía ya salió de la bodega
     * y el kardex lo tiene escrito: se corrige anulándola y emitiendo otra.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $return = app(PurchaseReturnRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($return !== null && $return->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar una devolución en borrador.',
            );
        }
    }
}
