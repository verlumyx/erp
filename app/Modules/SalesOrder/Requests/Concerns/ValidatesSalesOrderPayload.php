<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera del pedido más sus líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`, `discount_amount`) no se
 * validan porque no se capturan: los calcula el repositorio a partir de la
 * cantidad, el precio y los porcentajes de cada línea.
 */
trait ValidatesSalesOrderPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function salesOrderRules(): array
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
            'price_list_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_price_lists', 'id')->where('company_id', $companyId),
            ],
            'salesperson_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'client_reference' => ['nullable', 'string', 'max:60'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'uuid'],
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
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.list_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_id' => ['nullable', 'uuid'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.withholding_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function salesOrderMessages(): array
    {
        return [
            'client_id.required' => 'Selecciona el cliente del pedido.',
            'client_id.exists' => 'El cliente seleccionado no existe o está inactivo.',
            'client_address_id.exists' => 'La dirección de entrega no pertenece a ese cliente.',
            'warehouse_id.required' => 'Selecciona la bodega de despacho.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe o está inactiva.',
            'order_date.required' => 'La fecha del pedido es obligatoria.',
            'expected_date.after_or_equal' => 'La fecha comprometida no puede ser anterior a la del pedido.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'lines.required' => 'El pedido debe tener al menos una línea.',
            'lines.min' => 'El pedido debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo no existe, está inactivo o no es vendible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad de la línea debe ser mayor que cero.',
            'lines.*.unit_price.min' => 'El precio de la línea no puede ser negativo.',
            'lines.*.discount_percent.max' => 'El descuento de la línea no puede superar el 100%.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateSalesOrderInvariants(Validator $validator): void
    {
        /** Un artículo repetido en dos líneas descuadra la reserva de stock. */
        $seen = [];

        foreach ($this->input('lines', []) as $index => $line) {
            $key = ($line['item_id'] ?? '').':'.($line['measurement_unit_id'] ?? '');

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "lines.{$index}.item_id",
                    'Ese artículo ya está en otra línea del pedido con la misma unidad.',
                );
            }

            $seen[$key] = true;
        }
    }
}
