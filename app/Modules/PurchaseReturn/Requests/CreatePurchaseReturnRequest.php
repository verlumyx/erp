<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Requests;

use App\Modules\PurchaseReturn\Requests\Concerns\ValidatesPurchaseReturnPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreatePurchaseReturnRequest extends FormRequest
{
    use ValidatesPurchaseReturnPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-returns.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->purchaseReturnRules(),
        ];
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
        $validator->after(fn (Validator $validator) => $this->validatePurchaseReturnInvariants($validator));
    }
}
