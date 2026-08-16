<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('exchange-rates.create') ?? false;
    }

    /**
     * The combination currency + date + type is intentionally NOT validated as unique:
     * reloading it updates the existing rate instead of failing.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'currency' => ['required', 'string', 'size:3', 'in:USD,EUR'],
            'rate_date' => ['required', 'date_format:Y-m-d'],
            'rate' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', 'in:legal,manual'],
            'source' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency.required' => 'La moneda es obligatoria.',
            'currency.in' => 'La moneda debe ser USD o EUR.',
            'rate_date.required' => 'La fecha de vigencia es obligatoria.',
            'rate_date.date_format' => 'La fecha de vigencia debe tener el formato AAAA-MM-DD.',
            'rate.required' => 'El valor de la tasa es obligatorio.',
            'rate.numeric' => 'El valor de la tasa debe ser numérico.',
            'rate.min' => 'El valor de la tasa no puede ser negativo.',
            'type.required' => 'El tipo de tasa es obligatorio.',
            'type.in' => 'El tipo de tasa debe ser legal o manual.',
        ];
    }
}
