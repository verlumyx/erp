<?php

declare(strict_types=1);

namespace App\Modules\Route\Requests;

use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use App\Modules\Route\Services\RoutePendingWorkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlanRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('routes.plan') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stop_date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stop_date.required' => 'Indica el día que se planifica.',
            'stop_date.date' => 'La fecha del recorrido no es válida.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $route = app(RouteRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($route === null) {
                return;
            }

            if ($route->status !== 'active') {
                $validator->errors()->add(
                    'stop_date',
                    'Una ruta desactivada no se planifica.',
                );

                return;
            }

            $this->validateFitsInVehicle($validator, $route);
        });
    }

    /**
     * Lo que se va a repartir tiene que caber en el vehículo. Se mide contra
     * los despachos ya asignados a la ruta para ese día: son la carga real.
     *
     * Una capacidad en cero significa «sin declarar», y contra un límite que
     * nadie puso no se puede rechazar nada.
     */
    private function validateFitsInVehicle(Validator $validator, Route $route): void
    {
        if (! $route->hasDeclaredWeightCapacity() && ! $route->hasDeclaredVolumeCapacity()) {
            return;
        }

        $pending = app(RoutePendingWorkService::class);
        $stopDate = $this->string('stop_date')->toString();

        $load = $pending->load($pending->pendingDispatches($route, $stopDate));

        if ($route->hasDeclaredWeightCapacity() && $load['weight'] > (float) $route->vehicle_capacity_weight) {
            $validator->errors()->add(
                'stop_date',
                "La carga del día pesa {$load['weight']} y el vehículo aguanta {$route->vehicle_capacity_weight}.",
            );
        }

        if ($route->hasDeclaredVolumeCapacity() && $load['volume'] > (float) $route->vehicle_capacity_volume) {
            $validator->errors()->add(
                'stop_date',
                "La carga del día ocupa {$load['volume']} y en el vehículo caben {$route->vehicle_capacity_volume}.",
            );
        }
    }
}
