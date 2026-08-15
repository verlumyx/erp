<?php

declare(strict_types=1);

namespace App\Modules\Client\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ClientFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function email(string $value): Builder
    {
        return $this->builder->where('email', 'like', "%{$value}%");
    }

    public function phone(string $value): Builder
    {
        return $this->builder->where('phone', 'like', "%{$value}%");
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
