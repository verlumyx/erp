<?php

declare(strict_types=1);

namespace App\Modules\Sale\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales.cancel') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:255'],
            'create_refund' => ['sometimes', 'boolean'],
            'refund_amount' => ['nullable', 'required_if:create_refund,true', 'numeric', 'min:0.01'],
            'refund_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
