<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use Illuminate\Validation\Rule;

/**
 * Reglas compartidas por Create/Update: la cabecera del anticipo, que no tiene
 * líneas.
 *
 * `applied_amount` y `balance` no se validan porque no se capturan: los mueven
 * las aplicaciones a facturas. Que la orden de compra sea del mismo proveedor
 * se comprueba en `SupplierAdvanceOrderService`, no aquí.
 */
trait ValidatesSupplierAdvancePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function supplierAdvanceRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'supplier_id' => [
                'required',
                'uuid',
                Rule::exists('app_suppliers', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            /** Opcional: hay anticipos que no nacen de ninguna orden. */
            'purchase_order_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_purchase_orders', 'id')->where('company_id', $companyId),
            ],
            'advance_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(SupplierAdvance::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:60'],
            'bank_account' => ['nullable', 'string', 'max:60'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function supplierAdvanceMessages(): array
    {
        return [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no está disponible.',
            'purchase_order_id.exists' => 'La orden de compra indicada no existe en esta empresa.',
            'advance_date.required' => 'La fecha del anticipo es obligatoria.',
            'payment_method.required' => 'La forma de pago es obligatoria.',
            'payment_method.in' => 'La forma de pago indicada no existe.',
            'currency.required' => 'La moneda del anticipo es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'amount.required' => 'El monto del anticipo es obligatorio.',
            'amount.gt' => 'El monto del anticipo debe ser mayor que cero.',
        ];
    }
}
