<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la factura más sus
 * líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`, `discount_amount`) no se
 * validan porque no se capturan: los calcula el repositorio a partir de la
 * cantidad, el precio y los porcentajes de cada línea. El costo tampoco: se
 * congela al confirmar.
 */
trait ValidatesSalesInvoicePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function salesInvoiceRules(): array
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
            /** Alias del morph map, no el nombre de la clase. */
            'sourceable_type' => [
                'nullable',
                'string',
                Rule::in(SalesInvoice::SOURCE_TYPES),
                'required_with:sourceable_id',
            ],
            'sourceable_id' => ['nullable', 'uuid', 'required_with:sourceable_type'],
            /** El despacho no entra al morph: es un documento paralelo. */
            'dispatch_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_dispatches', 'id')->where('company_id', $companyId),
            ],
            'client_address_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_client_addresses', 'id')
                    ->where('company_id', $companyId)
                    ->where('client_id', $this->input('client_id')),
            ],
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'salesperson_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'invoice_series' => ['nullable', 'string', 'max:20'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'sale_type' => ['nullable', 'string', 'in:cash,credit'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'uuid'],
            'lines.*.sourceable_type' => [
                'nullable',
                'string',
                Rule::in(SalesInvoice::SOURCE_LINE_TYPES),
                'required_with:lines.*.sourceable_id',
            ],
            'lines.*.sourceable_id' => ['nullable', 'uuid', 'required_with:lines.*.sourceable_type'],
            'lines.*.item_id' => [
                'required',
                'uuid',
                Rule::exists('app_items', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active')
                    ->where('is_sellable', 'yes'),
            ],
            'lines.*.measurement_unit_id' => [
                'required',
                'uuid',
                Rule::exists('app_measurement_units', 'id')->where('company_id', $companyId),
            ],
            'lines.*.warehouse_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'lines.*.lot_id' => ['nullable', 'uuid'],
            'lines.*.serial_id' => ['nullable', 'uuid'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_taxes', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.withholding_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function salesInvoiceMessages(): array
    {
        return [
            'client_id.required' => 'Selecciona el cliente de la factura.',
            'client_id.exists' => 'El cliente seleccionado no existe o está inactivo.',
            'client_address_id.exists' => 'La dirección de entrega no pertenece a ese cliente.',
            'sourceable_type.in' => 'Ese tipo de documento origen no es válido para una factura.',
            'sourceable_type.required_with' => 'Indica el tipo del documento origen.',
            'sourceable_id.required_with' => 'Indica el documento origen.',
            'warehouse_id.required' => 'Selecciona la bodega de despacho.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe o está inactiva.',
            'invoice_date.required' => 'La fecha de la factura es obligatoria.',
            'due_date.required' => 'La fecha de vencimiento es obligatoria.',
            'due_date.after_or_equal' => 'El vencimiento no puede ser anterior a la fecha de la factura.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'lines.required' => 'La factura debe tener al menos una línea.',
            'lines.min' => 'La factura debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo no existe, está inactivo o no es vendible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad de la línea debe ser mayor que cero.',
            'lines.*.unit_price.min' => 'El precio de la línea no puede ser negativo.',
            'lines.*.discount_percent.max' => 'El descuento de la línea no puede superar el 100%.',
            'lines.*.tax_id.exists' => 'El impuesto de la línea no existe o está inactivo.',
            'lines.*.sourceable_type.in' => 'Ese tipo de línea origen no es válido para una factura.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateSalesInvoiceInvariants(Validator $validator): void
    {
        $this->validateNoRepeatedItems($validator);
        $this->validateSource($validator);
    }

    /**
     * Un artículo repetido en dos líneas descuadra la salida de inventario.
     */
    private function validateNoRepeatedItems(Validator $validator): void
    {
        $seen = [];

        foreach ($this->input('lines', []) as $index => $line) {
            $key = ($line['item_id'] ?? '').':'.($line['measurement_unit_id'] ?? '');

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "lines.{$index}.item_id",
                    'Ese artículo ya está en otra línea de la factura con la misma unidad.',
                );
            }

            $seen[$key] = true;
        }
    }

    /**
     * El documento origen no es un FK: su integridad se valida aquí.
     *
     * Debe ser del mismo cliente y de la misma empresa que la factura, y cada
     * línea origen tiene que pertenecer a ese documento y llevar la misma
     * unidad, porque de ella sale la cantidad ya facturada del pedido.
     *
     * Esa cantidad es también el tope: no se factura más de lo que al pedido le
     * queda por facturar. Se mide contra `invoiced_quantity`, que solo se mueve
     * al confirmar, así que un borrador todavía no consume saldo.
     */
    private function validateSource(Validator $validator): void
    {
        $sourceId = $this->input('sourceable_id');

        if ($this->input('sourceable_type') !== SalesOrder::MORPH_ALIAS || $sourceId === null) {
            $this->rejectOrphanLineSources($validator);

            return;
        }

        $order = SalesOrder::query()
            ->where('company_id', session('current_company_id'))
            ->find($sourceId);

        if ($order === null) {
            $validator->errors()->add('sourceable_id', 'El pedido de origen no existe en esta empresa.');

            return;
        }

        if ($order->client_id !== $this->input('client_id')) {
            $validator->errors()->add('sourceable_id', 'El pedido de origen es de otro cliente.');
        }

        if ($order->status === 'cancelled') {
            $validator->errors()->add('sourceable_id', 'No se puede facturar un pedido anulado.');
        }

        $orderLines = SalesOrderLine::query()
            ->where('sales_order_id', $order->id)
            ->get()
            ->keyBy('id');

        foreach ($this->input('lines', []) as $index => $line) {
            $lineSourceId = $line['sourceable_id'] ?? null;

            if ($lineSourceId === null) {
                continue;
            }

            $orderLine = $orderLines->get($lineSourceId);

            if ($orderLine === null) {
                $validator->errors()->add(
                    "lines.{$index}.sourceable_id",
                    'Esa línea no pertenece al pedido de origen.',
                );

                continue;
            }

            if ($orderLine->measurement_unit_id !== ($line['measurement_unit_id'] ?? null)) {
                $validator->errors()->add(
                    "lines.{$index}.measurement_unit_id",
                    'La unidad debe ser la misma que la de la línea del pedido.',
                );
            }

            $pending = max(
                round((float) $orderLine->quantity - (float) $orderLine->invoiced_quantity, 4),
                0,
            );

            if (round((float) ($line['quantity'] ?? 0), 4) > $pending) {
                $validator->errors()->add(
                    "lines.{$index}.quantity",
                    "Esa línea del pedido solo tiene {$pending} por facturar.",
                );
            }
        }
    }

    /**
     * Una línea no puede venir de un pedido si la factura no viene de ninguno.
     */
    private function rejectOrphanLineSources(Validator $validator): void
    {
        foreach ($this->input('lines', []) as $index => $line) {
            if (($line['sourceable_id'] ?? null) !== null) {
                $validator->errors()->add(
                    "lines.{$index}.sourceable_id",
                    'La factura no tiene documento origen: sus líneas tampoco pueden tenerlo.',
                );
            }
        }
    }
}
