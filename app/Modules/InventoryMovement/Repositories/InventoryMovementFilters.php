<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Repositories;

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementFilters extends EloquentQueryFilters
{
    /** Búsqueda libre por el código del movimiento o por el artículo. */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhereHas('item', function (Builder $item) use ($value): void {
                    $item->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('sku', 'like', "%{$value}%");
                });
        });
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
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

    public function serial_id(string $value): Builder
    {
        return $this->builder->where('serial_id', $value);
    }

    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    /** Solo entradas o solo salidas, sin distinguir el motivo. */
    public function direction(string $value): Builder
    {
        return $value === 'in'
            ? $this->builder->whereIn('type', InventoryMovement::INBOUND_TYPES)
            : $this->builder->whereNotIn('type', InventoryMovement::INBOUND_TYPES);
    }

    public function origin_type(string $value): Builder
    {
        return $this->builder->where('origin_type', $value);
    }

    public function origin_id(string $value): Builder
    {
        return $this->builder->where('origin_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /** Desde esta fecha contable, inclusive. */
    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('movement_date', '>=', $value);
    }

    /** Hasta esta fecha contable, inclusive. */
    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('movement_date', '<=', $value);
    }
}
