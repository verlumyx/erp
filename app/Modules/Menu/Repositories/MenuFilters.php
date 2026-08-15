<?php

declare(strict_types=1);

namespace App\Modules\Menu\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class MenuFilters extends EloquentQueryFilters
{
    public function permission(string $value): Builder
    {
        return $this->builder->where('permission', $value);
    }
}
