<?php

declare(strict_types=1);

namespace App\Modules\Account\Requests;

use App\Modules\Account\Models\Account;
use App\Modules\Service\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accounts.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'id' => ['required', 'uuid'],
            'service_id' => [
                'required',
                'uuid',
                Rule::exists('app_services', 'id')->where('company_id', $companyId),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('app_accounts', 'email')->where('service_id', $this->input('service_id')),
            ],
            'password' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['required', 'date'],
            'next_renewal' => ['required', 'date', 'after_or_equal:purchase_date'],
            'status' => ['nullable', Rule::in(Account::STATUSES)],
            'notes' => ['nullable', 'string'],
            'profiles' => ['nullable', 'array'],
            'profiles.*.number' => ['required_with:profiles', 'integer', 'min:1'],
            'profiles.*.pin' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $profiles = $this->input('profiles', []);

            if ($profiles === [] || $validator->errors()->hasAny(['service_id', 'profiles'])) {
                return;
            }

            $maxProfiles = (int) Service::query()
                ->where('id', $this->input('service_id'))
                ->where('company_id', session('current_company_id'))
                ->value('max_profiles');

            $seen = [];

            foreach ($profiles as $index => $profile) {
                $number = (int) ($profile['number'] ?? 0);

                if ($number < 1 || $number > $maxProfiles) {
                    $validator->errors()->add(
                        "profiles.{$index}.number",
                        "El número de perfil debe estar entre 1 y {$maxProfiles}."
                    );
                }

                if (isset($seen[$number])) {
                    $validator->errors()->add(
                        "profiles.{$index}.number",
                        "El número de perfil {$number} está duplicado."
                    );
                }

                $seen[$number] = true;
            }
        });
    }
}
