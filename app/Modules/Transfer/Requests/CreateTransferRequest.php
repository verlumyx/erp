<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Requests;

use App\Modules\Transfer\Requests\Concerns\ValidatesTransferPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateTransferRequest extends FormRequest
{
    use ValidatesTransferPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transfers.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->transferRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->transferMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateTransferInvariants($validator));
    }
}
