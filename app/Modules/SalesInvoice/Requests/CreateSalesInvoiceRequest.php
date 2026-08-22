<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Requests;

use App\Modules\SalesInvoice\Requests\Concerns\ValidatesSalesInvoicePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateSalesInvoiceRequest extends FormRequest
{
    use ValidatesSalesInvoicePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-invoices.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->salesInvoiceRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->salesInvoiceMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateSalesInvoiceInvariants($validator));
    }
}
