<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Repositories;

use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class SalesReturnFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del select remoto. Se llama `q` y no `search` para no
     * chocar con `SalesReturnRepository::search(SearchSalesReturnCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    /**
     * Hidratación de los valores ya elegidos en un formulario de edición:
     * ids separados por coma, tal como los manda `Select2Ajax`.
     */
    public function ids(string $value): Builder
    {
        return $this->builder->whereIn('id', array_filter(explode(',', $value)));
    }

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    /**
     * El nombre del método debe coincidir con la clave del filtro que llega
     * en el request (`client_id`), no con su versión camelCase.
     */
    public function client_id(string $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }

    public function sales_invoice_id(string $value): Builder
    {
        return $this->builder->where('sales_invoice_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function reason(string $value): Builder
    {
        return $this->builder->where('reason', $value);
    }

    /** En qué estado volvió la mercancía: `resalable`, `damaged` o `scrap`. */
    public function condition(string $value): Builder
    {
        return $this->builder->where('condition', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Devoluciones que una nota de crédito puede acreditar: la mercancía ya
     * reingresó y todavía no hay nota que la reconozca.
     */
    public function creditable(string $value): Builder
    {
        if ($value !== 'yes') {
            return $this->builder;
        }

        return $this->builder
            ->whereIn('status', SalesReturn::POSTED_STATUSES)
            ->whereNull('credit_note_id');
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('return_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('return_date', '<=', $value);
    }
}
