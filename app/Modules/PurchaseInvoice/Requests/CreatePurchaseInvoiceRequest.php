<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Requests;

use App\Modules\PurchaseInvoice\Requests\Concerns\ValidatesPurchaseInvoicePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreatePurchaseInvoiceRequest extends FormRequest
{
    use ValidatesPurchaseInvoicePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-invoices.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->purchaseInvoiceRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->purchaseInvoiceMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validatePurchaseInvoiceInvariants($validator));
    }
}
