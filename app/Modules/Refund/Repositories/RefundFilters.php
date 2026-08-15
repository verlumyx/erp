<?php

declare(strict_types=1);

namespace App\Modules\Refund\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class RefundFilters extends EloquentQueryFilters
{
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('reason', 'like', "%{$value}%");
        });
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function sale_id(string $value): Builder
    {
        return $this->builder->where('sale_id', $value);
    }

    public function client_id(string $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }
}
