<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class TransferFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del select remoto. Se llama `q` y no `search` para no
     * chocar con `TransferRepository::search(SearchTransferCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('vehicle_plate', 'like', "%{$value}%");
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

    /**
     * El nombre del método debe coincidir con la clave del filtro que llega
     * en el request (`origin_warehouse_id`), no con su versión camelCase.
     */
    public function origin_warehouse_id(string $value): Builder
    {
        return $this->builder->where('origin_warehouse_id', $value);
    }

    public function destination_warehouse_id(string $value): Builder
    {
        return $this->builder->where('destination_warehouse_id', $value);
    }

    /**
     * Traslados que tocan una bodega, sea de la que salen o a la que llegan.
     * Es la pregunta que se hace el bodeguero: «¿qué se mueve por aquí?».
     */
    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('origin_warehouse_id', $value)
                ->orWhere('destination_warehouse_id', $value)
                ->orWhere('transit_warehouse_id', $value);
        });
    }

    public function driver_id(string $value): Builder
    {
        return $this->builder->where('driver_id', $value);
    }

    public function route_id(string $value): Builder
    {
        return $this->builder->where('route_id', $value);
    }

    public function reason(string $value): Builder
    {
        return $this->builder->where('reason', $value);
    }

    public function transfer_status(string $value): Builder
    {
        return $this->builder->where('transfer_status', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('transfer_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('transfer_date', '<=', $value);
    }
}
