<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class SalesInvoiceFilters extends EloquentQueryFilters
{
    public function code(string $value): Builder
    {
        return $this->builder->where('code', 'like', "%{$value}%");
    }

    /**
     * Busca por el nombre del cliente, no por su id: es lo que el usuario tiene
     * a mano cuando abre el listado.
     */
    public function client(string $value): Builder
    {
        return $this->builder->whereHas(
            'client',
            fn (Builder $query) => $query->where('name', 'like', "%{$value}%")
                ->orWhere('legal_name', 'like', "%{$value}%"),
        );
    }

    public function client_id(string $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function salesperson_id(string $value): Builder
    {
        return $this->builder->where('salesperson_id', $value);
    }

    /** El correlativo fiscal, que no es el `code` del documento. */
    public function invoice_number(string $value): Builder
    {
        return $this->builder->where('invoice_number', 'like', "%{$value}%");
    }

    public function invoice_series(string $value): Builder
    {
        return $this->builder->where('invoice_series', $value);
    }

    public function currency(string $value): Builder
    {
        return $this->builder->where('currency', strtoupper($value));
    }

    public function sale_type(string $value): Builder
    {
        return $this->builder->where('sale_type', $value);
    }

    public function payment_status(string $value): Builder
    {
        return $this->builder->where('payment_status', $value);
    }

    public function invoice_date_from(string $value): Builder
    {
        return $this->builder->whereDate('invoice_date', '>=', $value);
    }

    public function invoice_date_to(string $value): Builder
    {
        return $this->builder->whereDate('invoice_date', '<=', $value);
    }

    public function due_date_from(string $value): Builder
    {
        return $this->builder->whereDate('due_date', '>=', $value);
    }

    public function due_date_to(string $value): Builder
    {
        return $this->builder->whereDate('due_date', '<=', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Búsqueda libre del select remoto: un solo término contra lo que el
     * usuario reconoce de una factura.
     *
     * Se llama `q` —como el parámetro que manda `Select2Ajax`— y no `search`
     * porque el repositorio, que hereda de esta clase, ya define `search()`.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('invoice_number', 'like', "%{$value}%");
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

    /** Varios estados a la vez, separados por coma (`confirmed,completed`). */
    public function statuses(string $value): Builder
    {
        return $this->builder->whereIn('status', array_filter(explode(',', $value)));
    }

    /**
     * Facturas que todavía deben algo. Es lo único que un cobro puede abonar;
     * de qué estados son válidos se encarga `statuses`.
     */
    public function open(string $value): Builder
    {
        if ($value !== 'yes') {
            return $this->builder;
        }

        return $this->builder->where('balance', '>', 0);
    }
}
