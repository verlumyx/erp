<?php

declare(strict_types=1);

namespace App\Modules\Pos\Requests;

use App\Modules\Pos\Models\PosCashMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePosCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pos-sessions.cash-movements') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('id') || ! $this->filled('id')) {
            $this->merge(['id' => (string) \Illuminate\Support\Str::uuid7()]);
        }

        if (! $this->has('pos_session_id') && $this->route('id')) {
            $this->merge(['pos_session_id' => $this->route('id')]);
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
            'movement_type' => ['required', 'string', Rule::in(PosCashMovement::MOVEMENT_TYPES)],
            'concept' => ['required', 'string', Rule::in(PosCashMovement::CONCEPTS)],
            'currency' => ['required', 'string', 'in:USD,VES,EUR'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'authorized_by' => ['nullable', 'uuid', 'exists:users,id'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'El monto es obligatorio.',
            'amount.gt' => 'El monto debe ser mayor a cero.',
            'reason.required' => 'Debes indicar el motivo del movimiento.',
            'movement_type.in' => 'El tipo de movimiento debe ser entrada o salida.',
        ];
    }
}
