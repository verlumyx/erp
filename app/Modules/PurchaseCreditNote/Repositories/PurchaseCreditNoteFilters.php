<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class PurchaseCreditNoteFilters extends EloquentQueryFilters
{
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

    public function purchase_invoice_id(string $value): Builder
    {
        return $this->builder->where('purchase_invoice_id', $value);
    }

    public function supplier_document_number(string $value): Builder
    {
        return $this->builder->where('supplier_document_number', 'like', "%{$value}%");
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
                ->orWhere('supplier_document_number', 'like', "%{$value}%");
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
