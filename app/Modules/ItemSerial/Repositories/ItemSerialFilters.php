<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ItemSerialFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del select remoto. Se llama `q` y no `search` para no
     * chocar con `ItemSerialRepository::search(SearchItemSerialCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('serial_number', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%");
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

    public function serial_number(string $value): Builder
    {
        return $this->builder->where('serial_number', 'like', "%{$value}%");
    }

    public function item_id(string $value): Builder
    {
        return $this->builder->where('item_id', $value);
    }

    public function lot_id(string $value): Builder
    {
        return $this->builder->where('lot_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
