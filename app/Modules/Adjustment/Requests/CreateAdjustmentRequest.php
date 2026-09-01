<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Requests;

use App\Modules\Adjustment\Requests\Concerns\ValidatesAdjustmentPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateAdjustmentRequest extends FormRequest
{
    use ValidatesAdjustmentPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('adjustments.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->adjustmentRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->adjustmentMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateAdjustmentInvariants($validator));
    }
}
