<?php

declare(strict_types=1);

namespace App\Modules\Item\Requests;

use App\Modules\Item\Requests\Concerns\ValidatesItemPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateItemRequest extends FormRequest
{
    use ValidatesItemPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('items.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'sku' => [
                'required',
                'string',
                'max:60',
                Rule::unique('app_items', 'sku')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('app_items', 'barcode')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            ...$this->itemRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->itemMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateItemInvariants($validator));
    }
}
