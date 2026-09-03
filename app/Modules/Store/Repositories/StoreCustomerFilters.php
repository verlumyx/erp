<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class StoreCustomerFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del listado y del select remoto. Se llama `q` y no
     * `search` porque el repositorio, que hereda de esta clase, ya define
     * `search()`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%")
                ->orWhere('document_number', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%");
        });
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /** `yes` = con cliente vinculado; `no` = sin vincular. */
    public function linked(string $value): Builder
    {
        return $value === 'yes'
            ? $this->builder->whereNotNull('client_id')
            : $this->builder->whereNull('client_id');
    }

    public function ids(string $value): Builder
    {
        return $this->builder->whereIn('id', array_filter(explode(',', $value)));
    }
}
