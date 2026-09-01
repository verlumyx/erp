<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Repositories;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class DispatchFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del select remoto. Se llama `q` y no `search` para no
     * chocar con `DispatchRepository::search(SearchDispatchCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('tracking_number', 'like', "%{$value}%");
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
     * en el request (`client_id`), no con su versión camelCase.
     */
    public function client_id(string $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function driver_id(string $value): Builder
    {
        return $this->builder->where('driver_id', $value);
    }

    public function route_id(string $value): Builder
    {
        return $this->builder->where('route_id', $value);
    }

    /** El pedido —u otro documento— del que salió el despacho. */
    public function sourceable_id(string $value): Builder
    {
        return $this->builder->where('sourceable_id', $value);
    }

    public function delivery_status(string $value): Builder
    {
        return $this->builder->where('delivery_status', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function tracking_number(string $value): Builder
    {
        return $this->builder->where('tracking_number', 'like', "%{$value}%");
    }

    /**
     * Despachos que una factura de venta puede facturar: la mercancía ya salió
     * de la bodega y el cliente se quedó con algo de ella.
     */
    public function invoiceable(string $value): Builder
    {
        if ($value !== 'yes') {
            return $this->builder;
        }

        return $this->builder
            ->whereIn('status', Dispatch::POSTED_STATUSES)
            ->whereNotIn('delivery_status', Dispatch::REFUSED_DELIVERY_STATUSES);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('dispatch_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('dispatch_date', '<=', $value);
    }
}
