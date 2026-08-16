<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusSupplierTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-types.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
