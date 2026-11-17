<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Requests;

use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusLoadPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('load-plans.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(LoadPlan::STATUSES)],
            'delivered_dispatch_ids' => ['nullable', 'array'],
            'delivered_dispatch_ids.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.in' => 'El estado indicado no es válido.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            $loadPlan = app(LoadPlanRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($loadPlan === null) {
                return;
            }

            $allowed = LoadPlan::STATUS_TRANSITIONS[$loadPlan->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el plan de carga de «{$loadPlan->status}» a «{$status}».",
                );
            }
        });
    }
}
