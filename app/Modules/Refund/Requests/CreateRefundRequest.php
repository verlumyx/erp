<?php

declare(strict_types=1);

namespace App\Modules\Refund\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('refunds.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'id' => ['required', 'uuid'],
            'sale_id' => [
                'required',
                'uuid',
                Rule::exists('app_sales', 'id')->where('company_id', $companyId),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'El identificador es obligatorio.',
            'sale_id.required' => 'La venta es obligatoria.',
            'sale_id.exists' => 'La venta no existe.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a 0.',
        ];
    }
}
