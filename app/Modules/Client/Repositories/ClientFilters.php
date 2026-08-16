<?php

declare(strict_types=1);

namespace App\Modules\Client\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ClientFilters extends EloquentQueryFilters
{
    public function name(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('legal_name', 'like', "%{$value}%");
        });
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

    /**
     * El nombre del método debe coincidir con la clave del filtro que llega
     * en el request (`document_number`), no con su versión camelCase.
     */
    public function document_number(string $value): Builder
    {
        return $this->builder->where('document_number', 'like', "%{$value}%");
    }

    public function document_type(string $value): Builder
    {
        return $this->builder->where('document_type', strtoupper($value));
    }

    public function client_type_id(string $value): Builder
    {
        return $this->builder->where('client_type_id', $value);
    }

    public function price_list_id(string $value): Builder
    {
        return $this->builder->where('price_list_id', $value);
    }

    public function salesperson_id(string $value): Builder
    {
        return $this->builder->where('salesperson_id', $value);
    }

    public function credit_blocked(string $value): Builder
    {
        return $this->builder->where('credit_blocked', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
