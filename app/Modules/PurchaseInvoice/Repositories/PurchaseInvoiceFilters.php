<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class PurchaseInvoiceFilters extends EloquentQueryFilters
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

    public function warehouse_id(string $value): Builder
    {
        return $this->builder->where('warehouse_id', $value);
    }

    public function supplier_invoice_number(string $value): Builder
    {
        return $this->builder->where('supplier_invoice_number', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function payment_status(string $value): Builder
    {
        return $this->builder->where('payment_status', $value);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('invoice_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('invoice_date', '<=', $value);
    }
}
