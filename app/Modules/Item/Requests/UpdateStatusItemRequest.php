<?php

declare(strict_types=1);

namespace App\Modules\Item\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('items.update-status') ?? false;
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
