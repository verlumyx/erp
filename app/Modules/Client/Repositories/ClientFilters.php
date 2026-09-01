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

    /**
     * Búsqueda libre del select remoto: un solo término contra todo lo que el
     * usuario reconoce de un cliente. Va agrupada para no romper el resto de
     * los filtros con el `or`.
     *
     * Se llama `q` —como el parámetro que manda `Select2Ajax`— y no `search`
     * porque el repositorio, que hereda de esta clase, ya define `search()`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('legal_name', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%")
                ->orWhere('document_number', 'like', "%{$value}%");
        });
    }

    /**
     * Hidratación de los valores ya elegidos en un formulario de edición:
     * ids separados por coma, tal como los manda `Select2Ajax`.
     */
    public function ids(string $value): Builder
    {
        return $this->builder->whereIn('id', array_filter(explode(',', $value)));
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Clientes que deben algo. Es lo único que se ofrece al empezar un cobro
     * por cliente: cobrarle a quien no debe nada solo genera un anticipo, y ese
     * camino es el suyo propio.
     */
    public function with_balance(string $value): Builder
    {
        if ($value !== 'yes') {
            return $this->builder;
        }

        return $this->builder->where('current_balance', '>', 0);
    }
}
