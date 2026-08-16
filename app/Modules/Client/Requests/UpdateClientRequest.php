<?php

declare(strict_types=1);

namespace App\Modules\Client\Requests;

use App\Modules\Client\Requests\Concerns\ValidatesClientPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClientRequest extends FormRequest
{
    use ValidatesClientPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('clients.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'document_number' => [
                'required',
                'string',
                'max:15',
                'regex:/^[0-9]+$/',
                Rule::unique('app_clients', 'document_number')
                    ->where('company_id', $companyId)
                    ->where('document_type', strtoupper((string) $this->input('document_type')))
                    ->ignore($this->route('id')),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('app_clients', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            ...$this->clientRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->clientMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateClientInvariants($validator));
    }
}
