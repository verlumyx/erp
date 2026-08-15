<?php

declare(strict_types=1);

namespace App\Modules\Shared\Repositories;

use Illuminate\Database\Eloquent\Builder;

class EloquentQueryFilters
{
    protected Builder $builder;

    /**
     * Apply the given filters to the query, skipping empty values and
     * dynamically calling the method whose name matches each filter key.
     *
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $builder, array $filters): Builder
    {
        $this->builder = $builder;

        foreach (array_filter($filters, fn ($value) => $value !== null && $value !== '') as $key => $value) {
            if (method_exists($this, $key)) {
                call_user_func([$this, $key], $value);
            }
        }

        return $this->builder;
    }
}
