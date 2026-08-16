<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusClientTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('client-types.update-status') ?? false;
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
