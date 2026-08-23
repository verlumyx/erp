<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Requests;

use App\Modules\ItemLot\Models\ItemLot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusItemLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('item-lots.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(ItemLot::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado del lote no es válido.',
        ];
    }
}
