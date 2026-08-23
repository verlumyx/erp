<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Requests;

use App\Modules\ItemSerial\Models\ItemSerial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusItemSerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('item-serials.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(ItemSerial::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado de la serie no es válido.',
        ];
    }
}
