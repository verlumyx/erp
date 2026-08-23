<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ItemStockFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre por artículo. El saldo no tiene `code` propio, así que se
     * busca contra el artículo al que pertenece.
     */
    public function q(string $value): Builder
    {
        return $this->builder->whereHas('item', function (Builder $query) use ($value): void {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%")
                ->orWhere('sku', 'like', "%{$value}%");
        });
    }

    public function item_id(string $value): Builder
    {
        return $this->builder->where('item_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function location_id(string $value): Builder
    {
        return $this->builder->where('location_id', $value);
    }

    public function lot_id(string $value): Builder
    {
        return $this->builder->where('lot_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /** Solo saldos con existencia: esconde las filas que quedaron en cero. */
    public function with_stock(string $value): Builder
    {
        return $value === 'yes'
            ? $this->builder->where('quantity', '>', 0)
            : $this->builder;
    }
}
