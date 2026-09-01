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
}
