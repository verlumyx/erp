<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class ImportFilters extends EloquentQueryFilters
{
    /**
     * Búsqueda libre. Se llama `q` y no `search` para no chocar con
     * `ImportRepository::search(SearchImportCommand)`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('reference', 'like', "%{$value}%");
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
     * en el request (`warehouse_id`), no con su versión camelCase.
     */
    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function reference(string $value): Builder
    {
        return $this->builder->where('reference', 'like', "%{$value}%");
    }

    public function allocation_method(string $value): Builder
    {
        return $this->builder->where('allocation_method', $value);
    }

    public function currency(string $value): Builder
    {
        return $this->builder->where('currency', strtoupper($value));
    }

    public function adjustment_id(string $value): Builder
    {
        return $this->builder->where('adjustment_id', $value);
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

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('import_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('import_date', '<=', $value);
    }
}
