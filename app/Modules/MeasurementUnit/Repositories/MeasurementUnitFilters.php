<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class MeasurementUnitFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'ilike', "%{$value}%");
    }

    public function abbreviation(string $value): Builder
    {
        return $this->builder->where('abbreviation', 'ilike', "%{$value}%");
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
