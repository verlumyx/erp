<?php

declare(strict_types=1);

namespace App\Modules\Entry\Repositories;

use App\Modules\Entry\Models\Entry;
use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class EntryFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre del select remoto. Se llama `q` y no `search` para no
     * chocar con `EntryRepository::search(SearchEntryCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('supplier_document', 'like', "%{$value}%");
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

    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    /**
     * El nombre del método debe coincidir con la clave del filtro que llega
     * en el request (`supplier_id`), no con su versión camelCase.
     */
    public function supplier_id(string $value): Builder
    {
        return $this->builder->where('supplier_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function entry_type(string $value): Builder
    {
        return $this->builder->where('entry_type', $value);
    }

    public function inspection_status(string $value): Builder
    {
        return $this->builder->where('inspection_status', $value);
    }

    public function supplier_document(string $value): Builder
    {
        return $this->builder->where('supplier_document', 'like', "%{$value}%");
    }

    public function is_invoiced(string $value): Builder
    {
        return $this->builder->where('is_invoiced', $value);
    }

    /** El documento origen: la orden de compra de la que salió la entrada. */
    public function sourceable_id(string $value): Builder
    {
        return $this->builder->where('sourceable_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /** Varios estados a la vez, separados por coma (`confirmed,completed`). */
    public function statuses(string $value): Builder
    {
        return $this->builder->whereIn('status', array_filter(explode(',', $value)));
    }

    /**
     * Entradas que una factura de compra podría respaldar: la mercancía ya
     * ingresó y todavía no hay factura que la reconozca.
     */
    public function invoiceable(string $value): Builder
    {
        if ($value !== 'yes') {
            return $this->builder;
        }

        return $this->builder
            ->whereIn('status', Entry::POSTED_STATUSES)
            ->where('is_invoiced', 'no');
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('entry_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('entry_date', '<=', $value);
    }
}
