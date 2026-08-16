<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Requests;

use App\Modules\SalesOrder\Requests\Concerns\ValidatesSalesOrderPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateSalesOrderRequest extends FormRequest
{
    use ValidatesSalesOrderPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-orders.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->salesOrderRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->salesOrderMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateSalesOrderInvariants($validator));
    }
}
