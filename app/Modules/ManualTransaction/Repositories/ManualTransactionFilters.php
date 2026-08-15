<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ManualTransactionFilters extends EloquentQueryFilters
{
    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function reference(string $value): Builder
    {
        return $this->builder->where('reference', 'like', "%{$value}%");
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('date', '<=', $value);
    }
}
