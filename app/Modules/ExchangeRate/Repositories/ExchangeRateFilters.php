<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ExchangeRateFilters extends EloquentQueryFilters
{
    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function currency(string $value): Builder
    {
        return $this->builder->where('currency', $value);
    }

    public function rate_date(string $value): Builder
    {
        return $this->builder->whereDate('rate_date', $value);
    }

    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
