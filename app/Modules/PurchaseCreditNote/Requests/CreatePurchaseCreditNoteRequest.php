<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Requests;

use App\Modules\PurchaseCreditNote\Requests\Concerns\ValidatesPurchaseCreditNotePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreatePurchaseCreditNoteRequest extends FormRequest
{
    use ValidatesPurchaseCreditNotePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase-credit-notes.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->purchaseCreditNoteRules(),
        ];
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
        $validator->after(fn (Validator $validator) => $this->validatePurchaseCreditNoteInvariants($validator));
    }
}
