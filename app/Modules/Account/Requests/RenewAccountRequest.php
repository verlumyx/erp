<?php

declare(strict_types=1);

namespace App\Modules\Account\Requests;

use App\Modules\Account\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RenewAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accounts.renew') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0'],
            'next_renewal' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * La nueva fecha de vencimiento debe ser posterior al vencimiento vigente
     * de la cuenta: una renovación siempre extiende el ciclo hacia adelante.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('next_renewal')) {
                return;
            }

            $currentRenewal = Account::query()
                ->where('id', $this->route('id'))
                ->where('company_id', session('current_company_id'))
                ->value('next_renewal');

            if ($currentRenewal === null) {
                return;
            }

            if (strtotime($this->input('next_renewal')) <= strtotime((string) $currentRenewal)) {
                $validator->errors()->add(
                    'next_renewal',
                    'La nueva fecha de vencimiento debe ser posterior al vencimiento actual.'
                );
            }
        });
    }
}
