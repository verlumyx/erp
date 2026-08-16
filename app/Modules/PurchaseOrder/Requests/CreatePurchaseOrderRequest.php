<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Requests;

use App\Modules\PurchaseOrder\Requests\Concerns\ValidatesPurchaseOrderPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreatePurchaseOrderRequest extends FormRequest
{
    use ValidatesPurchaseOrderPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-orders.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->purchaseOrderRules(),
        ];
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
        $validator->after(fn (Validator $validator) => $this->validatePurchaseOrderInvariants($validator));
    }
}
