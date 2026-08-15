<?php

declare(strict_types=1);

namespace App\Modules\Plan\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('plans.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'service_id' => [
                'required',
                'uuid',
                Rule::exists('app_services', 'id')->where('company_id', session('current_company_id')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'capacity' => ['required', 'string', 'in:profile,full_account'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'roi_target_pct' => ['required', 'numeric', 'min:0'],
        ];
    }
}
