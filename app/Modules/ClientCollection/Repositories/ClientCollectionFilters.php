<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ClientCollectionFilters extends EloquentQueryFilters
{
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

    public function reference(string $value): Builder
    {
        return $this->builder->where('reference', 'like', "%{$value}%");
    }

    public function payment_method(string $value): Builder
    {
        return $this->builder->where('payment_method', $value);
    }

    /** Cobrador o vendedor que recibió el dinero. */
    public function collected_by(string $value): Builder
    {
        return $this->builder->where('collected_by', $value);
    }

    public function route_id(string $value): Builder
    {
        return $this->builder->where('route_id', $value);
    }

    public function check_status(string $value): Builder
    {
        return $this->builder->where('check_status', $value);
    }

    public function origin_type(string $value): Builder
    {
        return $this->builder->where('origin_type', $value);
    }

    /** El documento del que nació el cobro: una factura o un anticipo. */
    public function origin_id(string $value): Builder
    {
        return $this->builder->where('origin_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('collection_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('collection_date', '<=', $value);
    }
}
