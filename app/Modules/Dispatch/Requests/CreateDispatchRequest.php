<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\Requests\Concerns\ValidatesDispatchPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateDispatchRequest extends FormRequest
{
    use ValidatesDispatchPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dispatches.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->dispatchRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->dispatchMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateDispatchInvariants($validator));
    }
}
