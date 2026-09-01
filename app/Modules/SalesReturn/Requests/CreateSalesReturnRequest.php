<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Requests;

use App\Modules\SalesReturn\Requests\Concerns\ValidatesSalesReturnPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateSalesReturnRequest extends FormRequest
{
    use ValidatesSalesReturnPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-returns.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->salesReturnRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->salesReturnMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateSalesReturnInvariants($validator));
    }
}
