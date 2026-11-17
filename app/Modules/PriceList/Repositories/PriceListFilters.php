<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class PriceListFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'ilike', "%{$value}%");
    }

    public function description(string $value): Builder
    {
        return $this->builder->where('description', 'ilike', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'ilike', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
