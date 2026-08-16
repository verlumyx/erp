<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Requests;

use App\Modules\Currency\Rules\ActiveCurrency;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('exchange-rates.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', new ActiveCurrency],
            'rate_date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('app_exchange_rates', 'rate_date')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('company_id', session('current_company_id'))
                        ->where('currency', $this->input('currency'))
                        ->where('type', $this->input('type')))
                    ->ignore($this->route('id')),
            ],
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
            'rate_date.required' => 'La fecha de vigencia es obligatoria.',
            'rate_date.date_format' => 'La fecha de vigencia debe tener el formato AAAA-MM-DD.',
            'rate_date.unique' => 'Ya existe una tasa para esa moneda, fecha y tipo.',
            'rate.required' => 'El valor de la tasa es obligatorio.',
            'rate.numeric' => 'El valor de la tasa debe ser numérico.',
            'rate.min' => 'El valor de la tasa no puede ser negativo.',
            'type.required' => 'El tipo de tasa es obligatorio.',
            'type.in' => 'El tipo de tasa debe ser legal o manual.',
        ];
    }
}
