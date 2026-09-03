<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class StoreOrderFilters extends EloquentQueryFilters
{
    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('buyer_name', 'like', "%{$value}%")
                ->orWhere('buyer_email', 'like', "%{$value}%");
        });
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('created_at', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('created_at', '<=', $value);
    }

    public function store_customer_id(string $value): Builder
    {
        return $this->builder->where('store_customer_id', $value);
    }

    public function client_id(string $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }
}
