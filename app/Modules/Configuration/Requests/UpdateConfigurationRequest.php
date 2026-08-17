<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Requests;

use App\Modules\Currency\Rules\ActiveCurrency;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('configuration.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'base_currency' => ['required', 'string', new ActiveCurrency],
            'secondary_currency' => ['nullable', 'string', new ActiveCurrency],
            'rate_type' => ['required', 'string', 'in:legal,manual'],
            'allows_rate_override' => ['required', 'string', 'in:yes,no'],
            'amount_decimals' => ['required', 'integer', 'between:0,6'],
            'price_decimals' => ['required', 'integer', 'between:0,8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'base_currency.required' => 'La moneda principal es obligatoria.',
            'rate_type.required' => 'El tipo de tasa es obligatorio.',
            'rate_type.in' => 'El tipo de tasa debe ser legal o manual.',
            'allows_rate_override.required' => 'Indica si la tasa se puede editar en los documentos.',
            'allows_rate_override.in' => 'Indica si la tasa se puede editar en los documentos.',
            'amount_decimals.required' => 'Los decimales de los importes son obligatorios.',
            'amount_decimals.between' => 'Los importes admiten entre 0 y 6 decimales.',
            'price_decimals.required' => 'Los decimales de los precios son obligatorios.',
            'price_decimals.between' => 'Los precios admiten entre 0 y 8 decimales.',
        ];
    }
}
