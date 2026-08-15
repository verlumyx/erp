<?php

declare(strict_types=1);

namespace App\Modules\Permission\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class PermissionFilters extends EloquentQueryFilters
{
    public function label(string $value): Builder
    {
        return $this->builder->where('label', 'like', "%{$value}%");
    }

    public function module_id(string $value): Builder
    {
        return $this->builder->where('module_id', $value);
    }

    public function is_active(mixed $value): Builder
    {
        return $this->builder->where('is_active', $value);
    }
}
