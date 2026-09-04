<?php

declare(strict_types=1);

namespace App\Modules\Import\Requests;

use App\Modules\Import\Requests\Concerns\ValidatesImportPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateImportRequest extends FormRequest
{
    use ValidatesImportPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('imports.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->importRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->importMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateImportInvariants($validator));
    }
}
