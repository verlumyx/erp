<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class StoreItemFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre: lo que el usuario reconoce de la publicación y del
     * artículo que hay detrás. Se llama `q` y no `search` porque el
     * repositorio, que hereda de esta clase, ya define `search()`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('app_store_items.title', 'like', "%{$value}%")
                ->orWhere('app_store_items.summary', 'like', "%{$value}%")
                ->orWhere('app_store_items.slug', 'like', "%{$value}%")
                ->orWhereHas('item', fn (Builder $item): Builder => $item
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('sku', 'like', "%{$value}%"));
        });
    }

    public function is_featured(string $value): Builder
    {
        return $this->builder->where('app_store_items.is_featured', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('app_store_items.status', $value);
    }

    /** La categoría no vive en la publicación: se filtra por la del artículo. */
    public function category_id(string $value): Builder
    {
        return $this->builder->whereHas('item', fn (Builder $item): Builder => $item->where('category_id', $value));
    }

    public function item_id(string $value): Builder
    {
        return $this->builder->where('app_store_items.item_id', $value);
    }

    public function ids(string $value): Builder
    {
        return $this->builder->whereIn('app_store_items.id', array_filter(explode(',', $value)));
    }
}
