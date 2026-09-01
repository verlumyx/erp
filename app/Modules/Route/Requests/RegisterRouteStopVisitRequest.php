<?php

declare(strict_types=1);

namespace App\Modules\Route\Requests;

use App\Modules\Route\Models\RouteStop;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterRouteStopVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('routes.visit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** `pending` no se registra: es de donde parte la parada. */
            'stop_status' => [
                'required',
                'string',
                Rule::in(array_values(array_diff(RouteStop::STOP_STATUSES, ['pending']))),
            ],
            'actual_arrival' => ['nullable', 'date'],
            'actual_departure' => ['nullable', 'date', 'after_or_equal:actual_arrival'],
            /** Solo se exige cuando no hubo visita: entonces hay que explicarlo. */
            'skip_reason' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn (): bool => in_array(
                    $this->input('stop_status'),
                    RouteStop::UNVISITED_STOP_STATUSES,
                    true,
                )),
            ],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stop_status.required' => 'Indica cómo terminó la visita.',
            'stop_status.in' => 'Ese resultado de la visita no existe.',
            'actual_departure.after_or_equal' => 'No se puede salir de la parada antes de llegar.',
            'skip_reason.required' => 'Explica por qué no se pudo visitar al cliente.',
            'latitude.between' => 'La latitud está fuera de rango.',
            'longitude.between' => 'La longitud está fuera de rango.',
        ];
    }

    /**
     * Una parada ya cerrada no se reescribe: lo que el conductor registró en la
     * calle es el hecho, y corregirlo sería reescribir la historia del día.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $route = app(RouteRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($route === null) {
                return;
            }

            $stop = app(RouteRepositoryInterface::class)
                ->findStop($route, (string) $this->route('stop'));

            if ($stop !== null && $stop->isClosed()) {
                $validator->errors()->add(
                    'stop_status',
                    'Esa parada ya está cerrada.',
                );
            }
        });
    }
}
