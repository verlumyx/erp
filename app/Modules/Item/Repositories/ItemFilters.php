<?php

declare(strict_types=1);

namespace App\Modules\Item\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ItemFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function sku(string $value): Builder
    {
        return $this->builder->where('sku', 'like', "%{$value}%");
    }

    public function barcode(string $value): Builder
    {
        return $this->builder->where('barcode', 'like', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    /**
     * El nombre del método debe coincidir con la clave del filtro que llega
     * en el request (`category_id`), no con su versión camelCase.
     */
    public function category_id(string $value): Builder
    {
        return $this->builder->where('category_id', $value);
    }

    public function is_sellable(string $value): Builder
    {
        return $this->builder->where('is_sellable', $value);
    }

    public function is_purchasable(string $value): Builder
    {
        return $this->builder->where('is_purchasable', $value);
    }

    /**
     * Búsqueda libre del select remoto: un solo término contra todo lo que el
     * usuario reconoce de un artículo. Va agrupada para no romper el resto de
     * los filtros con el `or`.
     *
     * Se llama `q` —como el parámetro que manda `Select2Ajax`— y no `search`
     * porque el repositorio, que hereda de esta clase, ya define `search()`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%")
                ->orWhere('sku', 'like', "%{$value}%")
                ->orWhere('barcode', 'like', "%{$value}%");
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

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
