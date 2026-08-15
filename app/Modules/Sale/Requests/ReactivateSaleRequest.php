<?php

declare(strict_types=1);

namespace App\Modules\Sale\Requests;

use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Requests\Concerns\ValidatesSaleProfiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReactivateSaleRequest extends FormRequest
{
    use ValidatesSaleProfiles;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales.reactivate') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'profile_ids' => ['nullable', 'array'],
            'profile_ids.*' => ['uuid'],
        ];
    }

    /**
     * Solo se puede reactivar una venta cancelada o expirada fuera del periodo de
     * gracia. Si el agente provee profiles de reemplazo, se valida su coherencia
     * contra el snapshot (servicio + capacidad) de la venta.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $sale = Sale::query()
                ->where('id', $this->route('id'))
                ->where('company_id', session('current_company_id'))
                ->first();

            if ($sale === null) {
                return;
            }

            if (! $sale->canBeReactivated()) {
                $validator->errors()->add(
                    'id',
                    'Esta venta no requiere reactivación. Usa la renovación.'
                );

                return;
            }

            $profileIds = array_values(array_map('strval', $this->input('profile_ids', [])));

            if ($profileIds !== []) {
                $this->validateProfilesCoherence(
                    $validator,
                    session('current_company_id'),
                    $sale->service_id,
                    $sale->capacity,
                    $profileIds,
                );
            }
        });
    }
}
