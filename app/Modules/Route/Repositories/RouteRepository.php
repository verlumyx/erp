<?php

declare(strict_types=1);

namespace App\Modules\Route\Repositories;

use App\Modules\Route\Commands\CreateRouteCommand;
use App\Modules\Route\Commands\RegisterRouteStopVisitCommand;
use App\Modules\Route\Commands\RouteClientData;
use App\Modules\Route\Commands\RouteStopData;
use App\Modules\Route\Commands\SearchRouteCommand;
use App\Modules\Route\Commands\UpdateRouteCommand;
use App\Modules\Route\Commands\UpdateStatusRouteCommand;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Models\RouteClient;
use App\Modules\Route\Models\RouteStop;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RouteRepository extends RouteFilters implements RouteRepositoryInterface
{
    public function create(CreateRouteCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $route = Route::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'description' => $command->description,
                'type' => $command->type,
                'warehouse_id' => $command->warehouseId,
                'driver_id' => $command->driverId,
                'salesperson_id' => $command->salespersonId,
                'vehicle_plate' => $command->vehiclePlate,
                'vehicle_capacity_weight' => $command->vehicleCapacityWeight,
                'vehicle_capacity_volume' => $command->vehicleCapacityVolume,
                'frequency' => $command->frequency,
                'weekdays' => $command->weekdays,
                'zone' => $command->zone,
                'city' => $command->city,
                'estimated_duration_minutes' => $command->estimatedDurationMinutes,
                'estimated_distance_km' => $command->estimatedDistanceKm,
                'notes' => $command->notes,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);

            $this->syncClients($route, $command->clients);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Route
    {
        return Route::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Route
    {
        return Route::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Route $model, UpdateRouteCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $model->update([
                'name' => $command->name,
                'description' => $command->description,
                'type' => $command->type,
                'warehouse_id' => $command->warehouseId,
                'driver_id' => $command->driverId,
                'salesperson_id' => $command->salespersonId,
                'vehicle_plate' => $command->vehiclePlate,
                'vehicle_capacity_weight' => $command->vehicleCapacityWeight,
                'vehicle_capacity_volume' => $command->vehicleCapacityVolume,
                'frequency' => $command->frequency,
                'weekdays' => $command->weekdays,
                'zone' => $command->zone,
                'city' => $command->city,
                'estimated_duration_minutes' => $command->estimatedDurationMinutes,
                'estimated_distance_km' => $command->estimatedDistanceKm,
                'notes' => $command->notes,
            ]);

            $this->syncClients($model, $command->clients);
        });
    }

    public function updateStatus(Route $model, UpdateStatusRouteCommand $command): void
    {
        $model->update(['status' => $command->status]);
    }

    /**
     * @return array{ data: Route[], total: int }
     */
    public function search(SearchRouteCommand $command): array
    {
        $query = Route::query()
            ->with(['warehouse', 'driver', 'salesperson'])
            ->withCount(['clients as clients_count' => fn ($q) => $q->where('status', 'active')])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('name')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, RouteClient>
     */
    public function activeClients(Route $model): array
    {
        return RouteClient::query()
            ->with(['client', 'clientAddress'])
            ->where('route_id', $model->id)
            ->where('status', 'active')
            ->orderBy('sequence')
            ->get()
            ->all();
    }

    /**
     * @return array<int, RouteStop>
     */
    public function stopsOn(Route $model, string $stopDate): array
    {
        return RouteStop::query()
            ->with(['client', 'clientAddress'])
            ->where('route_id', $model->id)
            ->whereDate('stop_date', $stopDate)
            ->where('status', 'active')
            ->orderBy('sequence')
            ->get()
            ->all();
    }

    public function findStop(Route $model, string $stopId): ?RouteStop
    {
        return RouteStop::query()
            ->with(['client', 'clientAddress'])
            ->where('route_id', $model->id)
            ->find($stopId);
    }

    /**
     * @param  array<int, RouteStopData>  $stops
     * @return array<int, RouteStop>
     */
    public function syncStops(Route $model, string $stopDate, array $stops): array
    {
        return DB::transaction(function () use ($model, $stopDate, $stops): array {
            $existing = RouteStop::query()
                ->where('route_id', $model->id)
                ->whereDate('stop_date', $stopDate)
                ->get()
                ->keyBy('client_id');

            $keep = [];

            foreach ($stops as $stop) {
                $current = $existing->get($stop->clientId);

                if ($current === null) {
                    $keep[] = RouteStop::create([
                        'company_id' => $model->company_id,
                        'route_id' => $model->id,
                        'client_id' => $stop->clientId,
                        'client_address_id' => $stop->clientAddressId,
                        'stop_date' => $stopDate,
                        'sequence' => $stop->sequence,
                        'stop_status' => 'pending',
                        'status' => 'active',
                    ])->id;

                    continue;
                }

                $keep[] = $current->id;

                /**
                 * Una parada ya visitada no se reescribe: replanificar el día
                 * no puede borrar lo que el conductor ya registró. Solo se
                 * vuelve a activar si estaba retirada.
                 */
                if ($current->isClosed()) {
                    $current->update(['status' => 'active']);

                    continue;
                }

                $current->update([
                    'client_address_id' => $stop->clientAddressId,
                    'sequence' => $stop->sequence,
                    'status' => 'active',
                ]);
            }

            /**
             * Lo que dejó de corresponder al día se desactiva, no se borra
             * (política de no borrado). Lo ya visitado se conserva activo: es
             * historia de la ruta, no plan.
             */
            RouteStop::query()
                ->where('route_id', $model->id)
                ->whereDate('stop_date', $stopDate)
                ->whereIn('stop_status', RouteStop::OPEN_STOP_STATUSES)
                ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
                ->update(['status' => 'inactive']);

            return $this->stopsOn($model, $stopDate);
        });
    }

    public function writeStopVisit(RouteStop $stop, RegisterRouteStopVisitCommand $command): RouteStop
    {
        $stop->update([
            'stop_status' => $command->stopStatus,
            'actual_arrival' => $command->actualArrival ?? $stop->actual_arrival,
            'actual_departure' => $command->actualDeparture ?? $stop->actual_departure,
            /** El motivo solo tiene sentido en una visita que no se hizo. */
            'skip_reason' => in_array($command->stopStatus, RouteStop::UNVISITED_STOP_STATUSES, true)
                ? $command->skipReason
                : null,
            'latitude' => $command->latitude ?? $stop->latitude,
            'longitude' => $command->longitude ?? $stop->longitude,
        ]);

        return $stop;
    }

    public function openStopsCount(Route $model): int
    {
        return RouteStop::query()
            ->where('route_id', $model->id)
            ->where('status', 'active')
            ->whereIn('stop_status', RouteStop::OPEN_STOP_STATUSES)
            ->count();
    }

    /**
     * @return array<int|string, mixed>
     */
    private function detailRelations(): array
    {
        return [
            'warehouse',
            'driver',
            'salesperson',
            'clients' => fn ($query) => $query->orderBy('sequence'),
            'clients.client',
            'clients.clientAddress',
        ];
    }

    /**
     * Alinea `app_route_clients` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id`; las que dejan de venir no
     * se borran, se desactivan (política de no borrado). Una fila inactiva
     * conserva su lugar en el índice único, así que volver a agregar al mismo
     * cliente la reactiva en vez de chocar contra la restricción.
     *
     * @param  array<int, RouteClientData>  $clients
     */
    private function syncClients(Route $route, array $clients): void
    {
        $existing = RouteClient::query()
            ->where('route_id', $route->id)
            ->get();

        $byId = $existing->keyBy('id');
        $byClient = $existing->keyBy(
            fn (RouteClient $row): string => $row->client_id.'|'.($row->client_address_id ?? ''),
        );

        $keep = [];

        foreach ($clients as $client) {
            $current = $client->id !== null ? $byId->get($client->id) : null;
            /** El mismo cliente con la misma dirección ya está: se reusa la fila. */
            $current ??= $byClient->get($client->clientId.'|'.($client->clientAddressId ?? ''));

            $attributes = [
                'company_id' => $route->company_id,
                'client_id' => $client->clientId,
                'client_address_id' => $client->clientAddressId,
                'sequence' => $client->sequence,
                'status' => $client->status,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = RouteClient::create([
                ...$attributes,
                'route_id' => $route->id,
            ])->id;
        }

        RouteClient::query()
            ->where('route_id', $route->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Generate the next sequential per-company code (RUT000001, RUT000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Route::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Route::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Route::CODE_PREFIX))) + 1
            : 1;

        return Route::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
