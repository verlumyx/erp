<?php

declare(strict_types=1);

namespace App\Modules\Route\Requests;

use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use App\Modules\Route\Services\RoutePendingWorkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('routes.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Route::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.in' => 'El estado indicado no existe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateNothingPending($validator));
    }

    /**
     * Una ruta con trabajo abierto no se desactiva: quedarían paradas sin
     * visitar y despachos asignados a un recorrido que ya nadie hace. Primero
     * se cierra el día, después se retira la ruta.
     */
    private function validateNothingPending(Validator $validator): void
    {
        if ($this->string('status')->toString() !== 'inactive') {
            return;
        }

        $route = app(RouteRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($route === null) {
            return;
        }

        $pending = app(RoutePendingWorkService::class);

        if ($pending->openStops($route) > 0) {
            $validator->errors()->add(
                'status',
                'La ruta tiene paradas sin cerrar: termínalas antes de desactivarla.',
            );

            return;
        }

        if ($pending->pendingDispatches($route) !== []) {
            $validator->errors()->add(
                'status',
                'La ruta tiene despachos sin entregar: resuélvelos antes de desactivarla.',
            );
        }
    }
}
