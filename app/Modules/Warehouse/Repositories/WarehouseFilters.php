<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class WarehouseFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function city(string $value): Builder
    {
        return $this->builder->where('city', 'like', "%{$value}%");
    }

    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    public function is_default(string $value): Builder
    {
        return $this->builder->where('is_default', $value);
    }

    public function uses_locations(string $value): Builder
    {
        return $this->builder->where('uses_locations', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
