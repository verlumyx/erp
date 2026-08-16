<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Requests;

use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\PurchaseOrder\Requests\Concerns\ValidatesPurchaseOrderPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePurchaseOrderRequest extends FormRequest
{
    use ValidatesPurchaseOrderPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-orders.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->purchaseOrderRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->purchaseOrderMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validatePurchaseOrderInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Una vez confirmada, la orden se modifica
     * anulándola y emitiendo una nueva: ya comprometió expectativa de entrada.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $order = app(PurchaseOrderRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($order !== null && $order->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar una orden en borrador.',
            );
        }
    }
}
