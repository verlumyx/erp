<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class SupplierPaymentFilters extends EloquentQueryFilters
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

    public function reference(string $value): Builder
    {
        return $this->builder->where('reference', 'like', "%{$value}%");
    }

    public function payment_method(string $value): Builder
    {
        return $this->builder->where('payment_method', $value);
    }

    public function origin_type(string $value): Builder
    {
        return $this->builder->where('origin_type', $value);
    }

    /** El documento del que nació el pago: una factura o un anticipo. */
    public function origin_id(string $value): Builder
    {
        return $this->builder->where('origin_id', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('payment_date', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('payment_date', '<=', $value);
    }
}
