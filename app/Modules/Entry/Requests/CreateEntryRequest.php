<?php

declare(strict_types=1);

namespace App\Modules\Entry\Requests;

use App\Modules\Entry\Requests\Concerns\ValidatesEntryPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateEntryRequest extends FormRequest
{
    use ValidatesEntryPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('entries.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->entryRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->entryMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateEntryInvariants($validator));
    }
}
