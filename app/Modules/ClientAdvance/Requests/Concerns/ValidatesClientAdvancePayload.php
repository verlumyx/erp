<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Requests\Concerns;

use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\Currency\Rules\ActiveCurrency;
use Illuminate\Validation\Rule;

/**
 * Reglas compartidas por Create/Update: la cabecera del anticipo, que no tiene
 * líneas.
 *
 * `applied_amount`, `balance` y `refunded_amount` no se validan porque no se
 * capturan: los mueven las aplicaciones a facturas. Que el pedido de venta sea
 * del mismo cliente se comprueba en `ClientAdvanceOrderService`, no aquí.
 */
trait ValidatesClientAdvancePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function clientAdvanceRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('app_clients', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            /** Opcional: hay anticipos que no nacen de ningún pedido. */
            'sales_order_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_sales_orders', 'id')->where('company_id', $companyId),
            ],
            'advance_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(ClientAdvance::PAYMENT_METHODS)],
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
    protected function clientAdvanceMessages(): array
    {
        return [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no está disponible.',
            'sales_order_id.exists' => 'El pedido de venta indicado no existe en esta empresa.',
            'advance_date.required' => 'La fecha del anticipo es obligatoria.',
            'payment_method.required' => 'La forma de cobro es obligatoria.',
            'payment_method.in' => 'La forma de cobro indicada no existe.',
            'currency.required' => 'La moneda del anticipo es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'amount.required' => 'El monto del anticipo es obligatorio.',
            'amount.gt' => 'El monto del anticipo debe ser mayor que cero.',
        ];
    }
}
