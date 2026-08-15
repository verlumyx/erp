<?php

declare(strict_types=1);

namespace App\Modules\Plan\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class PlanFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function capacity(string $value): Builder
    {
        return $this->builder->where('capacity', $value);
    }

    public function serviceId(string $value): Builder
    {
        return $this->builder->where('service_id', $value);
    }

    public function active(string $value): Builder
    {
        return $this->builder->where('active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
}
