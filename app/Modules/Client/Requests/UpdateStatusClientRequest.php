<?php

declare(strict_types=1);

namespace App\Modules\Client\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('clients.update-status') ?? false;
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
