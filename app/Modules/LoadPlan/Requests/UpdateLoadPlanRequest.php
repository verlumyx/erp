<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Requests;

use App\Modules\LoadPlan\Requests\Concerns\ValidatesLoadPlanPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateLoadPlanRequest extends FormRequest
{
    use ValidatesLoadPlanPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('load-plans.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->loadPlanRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            fn (Validator $validator) => $this->validateDispatches($validator, (string) $this->route('id'))
        );
    }
}
