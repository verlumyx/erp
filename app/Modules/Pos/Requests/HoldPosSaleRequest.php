<?php

declare(strict_types=1);

namespace App\Modules\Pos\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoldPosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pos-sales.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('id') || ! $this->filled('id')) {
            $this->merge(['id' => (string) \Illuminate\Support\Str::uuid7()]);
        }

        if (! $this->has('currency') || ! $this->filled('currency')) {
            $this->merge(['currency' => 'USD']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'id' => ['required', 'uuid'],
            'pos_session_id' => [
                'required',
                'uuid',
                Rule::exists('app_pos_sessions', 'id')->where('company_id', $companyId),
            ],
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('app_clients', 'id')->where('company_id', $companyId),
            ],
            'currency' => ['required', 'string', 'in:USD,VES,EUR'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'uuid'],
            'lines.*.measurement_unit_id' => ['required', 'uuid'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
