<?php

declare(strict_types=1);

namespace App\Modules\Pos\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenPosSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pos-sessions.open') ?? false;
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
            'pos_register_id' => [
                'required',
                'uuid',
                Rule::exists('app_pos_registers', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'opening_cash_usd' => ['nullable', 'numeric', 'min:0'],
            'opening_cash_ves' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pos_register_id.required' => 'Debes seleccionar una caja registradora.',
            'pos_register_id.exists' => 'La caja registradora seleccionada no existe o está inactiva.',
            'opening_cash_usd.min' => 'El fondo en USD no puede ser negativo.',
            'opening_cash_ves.min' => 'El fondo en VES no puede ser negativo.',
        ];
    }
}
