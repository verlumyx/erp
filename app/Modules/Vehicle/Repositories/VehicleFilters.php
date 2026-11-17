<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class VehicleFilters extends EloquentQueryFilters
{
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value) {
            $query->where('plate', 'ilike', "%{$value}%")
                ->orWhere('name', 'ilike', "%{$value}%")
                ->orWhere('code', 'ilike', "%{$value}%")
                ->orWhere('brand', 'ilike', "%{$value}%")
                ->orWhere('model', 'ilike', "%{$value}%");
        });
    }

    public function plate(string $value): Builder
    {
        return $this->builder->where('plate', 'ilike', "%{$value}%");
    }

    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'ilike', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'ilike', "%{$value}%");
    }

    public function brand(string $value): Builder
    {
        return $this->builder->where('brand', 'ilike', "%{$value}%");
    }

    public function model(string $value): Builder
    {
        return $this->builder->where('model', 'ilike', "%{$value}%");
    }

    public function vehicle_type(string $value): Builder
    {
        return $this->builder->where('vehicle_type', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Filtro para select remoto: permite buscar por lista de IDs separados por coma.
     */
    public function ids(string $value): Builder
    {
        $ids = array_filter(array_map('trim', explode(',', $value)));

        return empty($ids) ? $this->builder : $this->builder->whereIn('id', $ids);
    }
}
