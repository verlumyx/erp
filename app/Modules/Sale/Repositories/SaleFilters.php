<?php

declare(strict_types=1);

namespace App\Modules\Sale\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class SaleFilters extends EloquentQueryFilters
{
    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * @param  array<int, string>  $value
     */
    public function status_in(array $value): Builder
    {
        return $this->builder->whereIn('status', $value);
    }

    public function client_id(string $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }

    public function agent_id(string $value): Builder
    {
        return $this->builder->where('agent_id', $value);
    }

    public function service_id(string $value): Builder
    {
        return $this->builder->where('service_id', $value);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('start_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('start_date', '<=', $value);
    }

    public function end_date_from(string $value): Builder
    {
        return $this->builder->whereDate('end_date', '>=', $value);
    }

    public function end_date_to(string $value): Builder
    {
        return $this->builder->whereDate('end_date', '<=', $value);
    }

    public function expiring_soon(string $value): Builder
    {
        $days = (int) $value;

        return $this->builder
            ->where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
