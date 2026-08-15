<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class TransactionFilters extends EloquentQueryFilters
{
    public function type(string $value): Builder
    {
        return $this->builder->where('type', $value);
    }

    public function category(string $value): Builder
    {
        return $this->builder->where('category', $value);
    }

    public function payment_method(string $value): Builder
    {
        return $this->builder->where('payment_method', 'like', "%{$value}%");
    }

    public function reference(string $value): Builder
    {
        return $this->builder->where('reference', 'like', "%{$value}%");
    }

    public function related_type(string $value): Builder
    {
        return $this->builder->where('related_type', $value);
    }

    public function related_id(string $value): Builder
    {
        return $this->builder->where('related_id', $value);
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
