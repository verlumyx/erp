<?php

declare(strict_types=1);

namespace App\Modules\Pos\Requests;

use App\Modules\Pos\Models\PosPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PosCheckoutRequest extends FormRequest
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
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'igtf_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'gt:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'change_amount_usd' => ['nullable', 'numeric', 'min:0'],
            'change_amount_ves' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],

            // Lines
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => [
                'required',
                'uuid',
                Rule::exists('app_items', 'id')->where('company_id', $companyId),
            ],
            'lines.*.measurement_unit_id' => [
                'required',
                'uuid',
                Rule::exists('app_measurement_units', 'id')->where('company_id', $companyId),
            ],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', 'uuid'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total' => ['nullable', 'numeric', 'min:0'],

            // Payments
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'string', Rule::in(PosPayment::METHODS)],
            'payments.*.currency' => ['required', 'string', 'in:USD,VES,EUR'],
            'payments.*.amount_tendered' => ['required', 'numeric', 'gt:0'],
            'payments.*.amount_applied' => ['required', 'numeric', 'gt:0'],
            'payments.*.exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:60'],
            'payments.*.card_last_digits' => ['nullable', 'string', 'max:4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'El carrito no tiene productos.',
            'lines.min' => 'El carrito debe tener al menos un producto.',
            'payments.required' => 'Debe registrar al menos un método de pago.',
            'payments.min' => 'Debe registrar al menos un método de pago.',
            'total.gt' => 'El total de la venta debe ser mayor a cero.',
        ];
    }
}
