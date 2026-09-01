<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Repositories;

use App\Modules\Shared\Repositories\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderFilters extends EloquentQueryFilters
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

    public function client_reference(string $value): Builder
    {
        return $this->builder->where('client_reference', 'like', "%{$value}%");
    }

    public function currency(string $value): Builder
    {
        return $this->builder->where('currency', strtoupper($value));
    }

    public function order_date_from(string $value): Builder
    {
        return $this->builder->whereDate('order_date', '>=', $value);
    }

    public function order_date_to(string $value): Builder
    {
        return $this->builder->whereDate('order_date', '<=', $value);
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Búsqueda libre del select remoto: el usuario escribe el código del
     * pedido, la orden de compra del cliente o el nombre del cliente.
     */
    public function q(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('code', 'like', "%{$value}%")
                ->orWhere('client_reference', 'like', "%{$value}%")
                ->orWhereHas(
                    'client',
                    fn (Builder $client) => $client->where('name', 'like', "%{$value}%")
                        ->orWhere('legal_name', 'like', "%{$value}%"),
                );
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

    /**
     * Pedidos que todavía admiten factura. Un borrador no compromete nada y
     * uno anulado o cumplido ya no genera documentos nuevos.
     */
    public function invoiceable(string $value): Builder
    {
        return $value === 'yes'
            ? $this->builder->whereIn('status', ['confirmed', 'partial'])
            : $this->builder;
    }

    /**
     * Pedidos que todavía admiten despacho. Son los mismos estados que admiten
     * factura, pero el filtro se llama por lo que hace: la pantalla de
     * despachos no pide pedidos «facturables».
     */
    public function dispatchable(string $value): Builder
    {
        return $value === 'yes'
            ? $this->builder->whereIn('status', ['confirmed', 'partial'])
            : $this->builder;
    }
}
