<?php

declare(strict_types=1);

namespace App\Modules\Service\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ServiceFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function active(string $value): Builder
    {
        return $this->builder->where('active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
}
