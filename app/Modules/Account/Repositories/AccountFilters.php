<?php

declare(strict_types=1);

namespace App\Modules\Account\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class AccountFilters extends EloquentQueryFilters
{
    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function email(string $value): Builder
    {
        return $this->builder->where('email', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function service_id(string $value): Builder
    {
        return $this->builder->where('service_id', $value);
    }
}
