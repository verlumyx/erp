<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Requests;

use App\Modules\SupplierAdvance\Requests\Concerns\ValidatesSupplierAdvancePayload;
use Illuminate\Foundation\Http\FormRequest;

class CreateSupplierAdvanceRequest extends FormRequest
{
    use ValidatesSupplierAdvancePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-advances.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->supplierAdvanceRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->supplierAdvanceMessages();
    }
}
