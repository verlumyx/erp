<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Requests\Concerns;

use App\Modules\LoadPlan\Models\LoadPlanDispatch;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesLoadPlanPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function loadPlanRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'plan_date' => ['required', 'date'],
            'route_id' => [
                'required',
                'uuid',
                Rule::exists('app_routes', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'vehicle_id' => [
                'required',
                'uuid',
                Rule::exists('app_vehicles', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'driver_name' => ['required', 'string', 'max:150'],
            'driver_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'helper_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'dispatches' => ['nullable', 'array'],
            'dispatches.*.dispatch_id' => [
                'required',
                'uuid',
                Rule::exists('app_dispatches', 'id')
                    ->where('company_id', $companyId),
            ],
            'dispatches.*.sequence' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_date.required' => 'La fecha del plan de carga es obligatoria.',
            'plan_date.date' => 'La fecha del plan de carga no tiene un formato válido.',
            'route_id.required' => 'La ruta o flete de entrega es obligatoria.',
            'route_id.exists' => 'La ruta seleccionada no existe o está inactiva.',
            'warehouse_id.required' => 'La bodega de origen es obligatoria.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe o está inactiva.',
            'vehicle_id.required' => 'El camión asignado es obligatorio.',
            'vehicle_id.exists' => 'El camión seleccionado no existe o no está activo.',
            'driver_name.required' => 'El nombre del conductor es obligatorio.',
        ];
    }

    protected function validateDispatches(Validator $validator, ?string $currentPlanId = null): void
    {
        $rows = $this->input('dispatches', []);
        $seen = [];

        foreach ($rows as $index => $row) {
            $dispatchId = $row['dispatch_id'] ?? null;
            if (! $dispatchId) {
                continue;
            }

            if (isset($seen[$dispatchId])) {
                $validator->errors()->add(
                    "dispatches.{$index}.dispatch_id",
                    'El despacho está duplicado en este plan de carga.',
                );
            }
            $seen[$dispatchId] = true;

            // Check if dispatch is already in another active load plan
            $alreadyAssigned = LoadPlanDispatch::query()
                ->where('dispatch_id', $dispatchId)
                ->where('status', 'active')
                ->whereHas('loadPlan', function ($q) use ($currentPlanId) {
                    $q->whereIn('status', ['draft', 'in_transit'])
                        ->when($currentPlanId, fn ($sq) => $sq->where('id', '!=', $currentPlanId));
                })
                ->exists();

            if ($alreadyAssigned) {
                $validator->errors()->add(
                    "dispatches.{$index}.dispatch_id",
                    'El despacho ya se encuentra asignado a otro plan de carga en proceso o en tránsito.',
                );
            }
        }
    }
}
