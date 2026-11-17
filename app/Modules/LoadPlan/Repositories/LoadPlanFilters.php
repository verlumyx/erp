<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class LoadPlanFilters extends EloquentQueryFilters
{
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value) {
            $query->where('code', 'ilike', "%{$value}%")
                ->orWhere('driver_name', 'ilike', "%{$value}%")
                ->orWhere('helper_name', 'ilike', "%{$value}%")
                ->orWhereHas('route', fn ($rq) => $rq->where('name', 'ilike', "%{$value}%")->orWhere('code', 'ilike', "%{$value}%"))
                ->orWhereHas('vehicle', fn ($vq) => $vq->where('plate', 'ilike', "%{$value}%")->orWhere('name', 'ilike', "%{$value}%"));
        });
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'ilike', "%{$value}%");
    }

    public function route_id(string $value): Builder
    {
        return $this->builder->where('route_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function vehicle_id(string $value): Builder
    {
        return $this->builder->where('vehicle_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('plan_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('plan_date', '<=', $value);
    }
}
