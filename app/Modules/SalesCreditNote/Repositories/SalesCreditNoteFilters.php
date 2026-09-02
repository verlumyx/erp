<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class SalesCreditNoteFilters extends EloquentQueryFilters
{
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

    /** El correlativo fiscal, que no es el `code` del documento. */
    public function note_number(string $value): Builder
    {
        return $this->builder->where('note_number', 'like', "%{$value}%");
    }

    public function note_series(string $value): Builder
    {
        return $this->builder->where('note_series', $value);
    }

    public function reason(string $value): Builder
    {
        return $this->builder->where('reason', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('note_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('note_date', '<=', $value);
    }

    /**
     * Búsqueda libre del select remoto: un solo término contra lo que el
     * usuario reconoce de una nota.
     *
     * Se llama `q` —como el parámetro que manda `Select2Ajax`— y no `search`
     * porque el repositorio, que hereda de esta clase, ya define `search()`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('note_number', 'like', "%{$value}%");
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

    /** Varios estados a la vez, separados por coma (`confirmed`). */
    public function statuses(string $value): Builder
    {
        return $this->builder->whereIn('status', array_filter(explode(',', $value)));
    }

    /**
     * Notas que todavía tienen crédito disponible: lo único que se puede
     * aplicar a una factura.
     */
    public function open(string $value): Builder
    {
        if ($value !== 'yes') {
            return $this->builder;
        }

        return $this->builder->where('balance', '>', 0);
    }
}
