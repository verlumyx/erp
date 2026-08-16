<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class WarehouseLocationFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function location_code(string $value): Builder
    {
        return $this->builder->where('location_code', 'like', "%{$value}%");
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    public function is_default(string $value): Builder
    {
        return $this->builder->where('is_default', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
