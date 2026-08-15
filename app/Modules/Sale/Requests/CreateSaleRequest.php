<?php

declare(strict_types=1);

namespace App\Modules\Sale\Requests;

use App\Modules\Client\Models\Client;
use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Requests\Concerns\ValidatesSaleProfiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateSaleRequest extends FormRequest
{
    use ValidatesSaleProfiles;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'id' => ['required', 'uuid'],
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('app_clients', 'id')->where('company_id', $companyId),
            ],
            'plan_id' => [
                'required',
                'uuid',
                Rule::exists('app_plans', 'id')->where('company_id', $companyId),
            ],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['uuid'],
        ];
    }

    /**
     * Reglas de negocio cruzadas (coherencia de servicio, disponibilidad, capacidad,
     * cliente activo). La disponibilidad se vuelve a verificar con lock dentro de la
     * transacción del repositorio para blindar contra condiciones de carrera.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $companyId = session('current_company_id');

            $this->validateClientIsActive($validator, $companyId);

            /** @var Plan|null $plan */
            $plan = Plan::query()
                ->where('id', $this->input('plan_id'))
                ->where('company_id', $companyId)
                ->first();

            if ($plan === null) {
                return;
            }

            $this->validateProfilesCoherence(
                $validator,
                $companyId,
                $plan->service_id,
                $plan->capacity,
                array_values(array_map('strval', $this->input('profile_ids', []))),
            );
        });
    }

    private function validateClientIsActive(Validator $validator, ?string $companyId): void
    {
        $status = Client::query()
            ->where('id', $this->input('client_id'))
            ->where('company_id', $companyId)
            ->value('status');

        if ($status !== null && $status !== 'active') {
            $validator->errors()->add('client_id', 'Solo se puede vender a clientes activos.');
        }
    }
}
