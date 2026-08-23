<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ItemLotFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del select remoto. Se llama `q` y no `search` para no
     * chocar con `ItemLotRepository::search(SearchItemLotCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('lot_number', 'like', "%{$value}%")
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

    public function lot_number(string $value): Builder
    {
        return $this->builder->where('lot_number', 'like', "%{$value}%");
    }

    public function item_id(string $value): Builder
    {
        return $this->builder->where('item_id', $value);
    }

    public function supplier_id(string $value): Builder
    {
        return $this->builder->where('supplier_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /** Lotes que vencen en o antes de la fecha dada: el filtro de "por vencer". */
    public function expires_before(string $value): Builder
    {
        return $this->builder->whereNotNull('expires_at')->whereDate('expires_at', '<=', $value);
    }
}
