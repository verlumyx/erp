<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusPriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('price-lists.update-status') ?? false;
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
