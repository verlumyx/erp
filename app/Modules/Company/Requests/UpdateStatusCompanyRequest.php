<?php

declare(strict_types=1);

namespace App\Modules\Company\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_system_owner ?? false;
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
