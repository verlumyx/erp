<?php

declare(strict_types=1);

namespace App\Modules\Route\Requests\Concerns;

use App\Modules\Client\Models\ClientAddress;
use App\Modules\Route\Models\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la ruta más sus clientes
 * fijos.
 *
 * No hay nada de dinero que validar: la ruta planifica el viaje, no factura ni
 * mueve inventario. Lo único que se mide es la capacidad del vehículo, y sirve
 * para contrastarla al planificar el día, no para guardarla.
 */
trait ValidatesRoutePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function routeRules(): array
    {
        $companyId = session('current_company_id');

        return [
            /** El nombre identifica la ruta ante el usuario: único por empresa. */
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('app_routes', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(Route::TYPES)],
            'warehouse_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'driver_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'salesperson_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'vehicle_capacity_weight' => ['nullable', 'numeric', 'min:0'],
            'vehicle_capacity_volume' => ['nullable', 'numeric', 'min:0'],
            'frequency' => ['required', 'string', Rule::in(Route::FREQUENCIES)],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['string', Rule::in(Route::WEEKDAYS)],
            'zone' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'estimated_distance_km' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            /** Una ruta puede nacer vacía: los clientes se le van agregando. */
            'clients' => ['nullable', 'array'],
            'clients.*.id' => ['nullable', 'uuid'],
            'clients.*.client_id' => [
                'required',
                'uuid',
                Rule::exists('app_clients', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'clients.*.client_address_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_client_addresses', 'id')->where('company_id', $companyId),
            ],
            'clients.*.sequence' => ['nullable', 'integer', 'min:0'],
            'clients.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function routeMessages(): array
    {
        return [
            'name.required' => 'La ruta necesita un nombre.',
            'name.unique' => 'Ya existe una ruta con ese nombre en esta empresa.',
            'type.required' => 'Indica para qué se recorre la ruta.',
            'type.in' => 'Ese tipo de ruta no existe.',
            'warehouse_id.exists' => 'La bodega de salida no está disponible.',
            'driver_id.exists' => 'El conductor indicado no existe.',
            'salesperson_id.exists' => 'El vendedor indicado no existe.',
            'frequency.required' => 'Indica cada cuánto se recorre la ruta.',
            'frequency.in' => 'Esa frecuencia no existe.',
            'weekdays.*.in' => 'Ese día de la semana no existe.',
            'vehicle_capacity_weight.min' => 'La capacidad en peso no puede ser negativa.',
            'vehicle_capacity_volume.min' => 'La capacidad en volumen no puede ser negativa.',
            'clients.*.client_id.required' => 'Selecciona el cliente de la parada.',
            'clients.*.client_id.exists' => 'El cliente seleccionado no está disponible.',
            'clients.*.client_address_id.exists' => 'La dirección no existe en esta empresa.',
        ];
    }

    /**
     * Reglas que dependen de varias filas a la vez y no caben en `rules()`.
     */
    protected function validateRouteInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $clients */
        $clients = $this->input('clients', []);

        $this->validateNoRepeatedClients($validator, $clients);
        $this->validateAddressesBelongToClient($validator, $clients);
    }

    /**
     * El mismo cliente con la misma dirección no se visita dos veces en la
     * misma ruta. La base lo impide con un índice único, pero con la dirección
     * vacía no puede —en Postgres dos NULL son distintos—, así que la regla
     * vive también aquí.
     *
     * @param  array<int, array<string, mixed>>  $clients
     */
    private function validateNoRepeatedClients(Validator $validator, array $clients): void
    {
        $seen = [];

        foreach ($clients as $index => $client) {
            if (($client['status'] ?? 'active') !== 'active') {
                continue;
            }

            $clientId = $client['client_id'] ?? null;

            if ($clientId === null) {
                continue;
            }

            $key = $clientId.'|'.($client['client_address_id'] ?? '');

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "clients.{$index}.client_id",
                    'Ese cliente ya está en la ruta con la misma dirección.',
                );

                continue;
            }

            $seen[$key] = true;
        }
    }

    /**
     * La dirección que se visita tiene que ser del cliente al que se visita.
     *
     * @param  array<int, array<string, mixed>>  $clients
     */
    private function validateAddressesBelongToClient(Validator $validator, array $clients): void
    {
        $addressIds = array_values(array_filter(array_column($clients, 'client_address_id')));

        if ($addressIds === []) {
            return;
        }

        $ownerOf = ClientAddress::query()
            ->whereIn('id', $addressIds)
            ->pluck('client_id', 'id')
            ->all();

        foreach ($clients as $index => $client) {
            $addressId = $client['client_address_id'] ?? null;

            if (blank($addressId)) {
                continue;
            }

            if (($ownerOf[$addressId] ?? null) !== ($client['client_id'] ?? null)) {
                $validator->errors()->add(
                    "clients.{$index}.client_address_id",
                    'La dirección no pertenece a ese cliente.',
                );
            }
        }
    }
}
