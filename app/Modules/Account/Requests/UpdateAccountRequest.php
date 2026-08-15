<?php

declare(strict_types=1);

namespace App\Modules\Account\Requests;

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountRequest extends FormRequest
{
    private ?Account $account = null;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accounts.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $account = $this->resolveAccount();
        $serviceId = $account?->service_id;

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('app_accounts', 'email')
                    ->where('service_id', $serviceId)
                    ->ignore($this->route('id')),
            ],
            'password' => ['nullable', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['required', 'date'],
            'next_renewal' => ['required', 'date', 'after_or_equal:purchase_date'],
            'status' => ['required', Rule::in(Account::STATUSES)],
            'notes' => ['nullable', 'string'],
            'profiles' => ['nullable', 'array'],
            'profiles.*.number' => ['required_with:profiles', 'integer', 'min:1'],
            'profiles.*.pin' => ['nullable', 'string', 'max:10'],
            'profiles.*.status' => ['nullable', Rule::in(Profile::STATUSES)],
            'profiles.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $profiles = $this->input('profiles', []);
            $account = $this->resolveAccount();

            if ($profiles === [] || $account === null || $validator->errors()->has('profiles')) {
                return;
            }

            $validNumbers = $account->profiles()->pluck('number')->all();

            foreach ($profiles as $index => $profile) {
                $number = (int) ($profile['number'] ?? 0);

                if (! in_array($number, $validNumbers, true)) {
                    $validator->errors()->add(
                        "profiles.{$index}.number",
                        "El perfil {$number} no pertenece a esta cuenta."
                    );
                }
            }
        });
    }

    private function resolveAccount(): ?Account
    {
        if ($this->account !== null) {
            return $this->account;
        }

        return $this->account = Account::query()
            ->where('company_id', session('current_company_id'))
            ->find($this->route('id'));
    }
}
