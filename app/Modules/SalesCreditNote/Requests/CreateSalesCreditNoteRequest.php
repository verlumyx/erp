<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Requests;

use App\Modules\SalesCreditNote\Requests\Concerns\ValidatesSalesCreditNotePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateSalesCreditNoteRequest extends FormRequest
{
    use ValidatesSalesCreditNotePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-credit-notes.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->salesCreditNoteRules(),
        ];
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
        $validator->after(fn (Validator $validator) => $this->validateSalesCreditNoteInvariants($validator));
    }
}
