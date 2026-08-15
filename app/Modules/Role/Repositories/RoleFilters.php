<?php

declare(strict_types=1);

namespace App\Modules\Role\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class RoleFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function description(string $value): Builder
    {
        return $this->builder->where('description', 'like', "%{$value}%");
    }
}
