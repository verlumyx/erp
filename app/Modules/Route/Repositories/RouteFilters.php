<?php

declare(strict_types=1);

namespace App\Modules\Route\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class RouteFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del select remoto. Se llama `q` y no `search` para no
     * chocar con `RouteRepository::search(SearchRouteCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('name', 'like', "%{$value}%")
                ->orWhere('zone', 'like', "%{$value}%");
        });
    }

    /**
     * Hidratación de los valores ya elegidos en un formulario de edición:
     * ids separados por coma, tal como los manda `Select2Ajax`.
     */
    public function ids(string $value): Builder
    {
        return $this->builder->whereIn('id', array_filter(explode(',', $value)));
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    /**
     * El nombre del método debe coincidir con la clave del filtro que llega
     * en el request (`warehouse_id`), no con su versión camelCase.
     */
    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function driver_id(string $value): Builder
    {
        return $this->builder->where('driver_id', $value);
    }

    public function salesperson_id(string $value): Builder
    {
        return $this->builder->where('salesperson_id', $value);
    }

    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    public function frequency(string $value): Builder
    {
        return $this->builder->where('frequency', $value);
    }

    public function zone(string $value): Builder
    {
        return $this->builder->where('zone', 'like', "%{$value}%");
    }

    public function city(string $value): Builder
    {
        return $this->builder->where('city', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Rutas que visitan a un cliente. Se pregunta sobre la plantilla, no sobre
     * las paradas ya recorridas: la pregunta es «¿por dónde le llega?».
     */
    public function client_id(string $value): Builder
    {
        return $this->builder->whereHas(
            'clients',
            fn (Builder $query) => $query->where('client_id', $value)->where('status', 'active'),
        );
    }
}
