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

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
