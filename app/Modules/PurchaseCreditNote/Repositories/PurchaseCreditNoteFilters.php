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
}
