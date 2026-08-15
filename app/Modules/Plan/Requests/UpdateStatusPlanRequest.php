<?php

declare(strict_types=1);

namespace App\Modules\Plan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('plans.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }
}
