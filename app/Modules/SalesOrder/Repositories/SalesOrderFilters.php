<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderFilters extends EloquentQueryFilters
{
    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    /**
     * Busca por el nombre del cliente, no por su id: es lo que el usuario tiene
     * a mano cuando abre el listado.
     */
    public function client(string $value): Builder
    {
        return $this->builder->whereHas(
            'client',
            fn (Builder $query) => $query->where('name', 'like', "%{$value}%")
                ->orWhere('legal_name', 'like', "%{$value}%"),
        );
    }

    public function client_id(string $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function salesperson_id(string $value): Builder
    {
        return $this->builder->where('salesperson_id', $value);
    }

    public function client_reference(string $value): Builder
    {
        return $this->builder->where('client_reference', 'like', "%{$value}%");
    }

    public function currency(string $value): Builder
    {
        return $this->builder->where('currency', strtoupper($value));
    }

    public function order_date_from(string $value): Builder
    {
        return $this->builder->whereDate('order_date', '>=', $value);
    }

    public function order_date_to(string $value): Builder
    {
        return $this->builder->whereDate('order_date', '<=', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
