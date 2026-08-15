<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class UserFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function email(string $value): Builder
    {
        return $this->builder->where('email', 'like', "%{$value}%");
    }

    public function email_verified(mixed $value): Builder
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $this->builder->whereNotNull('email_verified_at')
            : $this->builder->whereNull('email_verified_at');
    }
}
