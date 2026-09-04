<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la devolución más sus
 * líneas.
 *
 * La línea captura qué vuelve, cuánto y a qué bodega. Ni el precio ni sus
 * cargos se validan porque no se capturan: los copia `SalesReturnPricingService`
 * de la línea facturada, y el costo de reingreso lo resuelve
 * `SalesReturnCostService` con el congelado en la venta original. Tampoco el
 * lote, la serie ni la ubicación: eso lo pide la entrada que la devolución
 * genera. La condición es de la cabecera, que es la que decide a qué bodega
 * puede reingresar lo devuelto.
 *
 * Lo que no cabe aquí porque exige leer la factura —que las líneas devueltas
 * sean suyas y que no se devuelva más de lo facturado— lo comprueba
 * `SalesReturnLimitsService` antes de guardar.
 */
trait ValidatesSalesReturnPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function salesReturnRules(): array
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
            /** Vacía cuando la mercancía vuelve sin factura de por medio. */
            'sales_invoice_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_sales_invoices', 'id')->where('company_id', $companyId),
            ],
            /** Despacho del que salió la mercancía que ahora vuelve. */
            'dispatch_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_dispatches', 'id')->where('company_id', $companyId),
            ],
            /** La bodega de la cabecera es la de la entrada que la devolución genera. */
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', Rule::in(SalesReturn::REASONS)],
            /** Obligatorio cuando el motivo es `other`: si no, no se sabe por qué. */
            'reason_detail' => [
                Rule::requiredIf(fn (): bool => $this->input('reason') === 'other'),
                'nullable',
                'string',
                'max:500',
            ],
            'condition' => ['required', 'string', Rule::in(SalesReturn::CONDITIONS)],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            /** Quién recibió la mercancía: un usuario de la empresa. */
            'received_by' => [
                'nullable',
                'uuid',
                Rule::exists('users', 'id'),
            ],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'uuid'],
            'lines.*.item_id' => [
                'required',
                'uuid',
                Rule::exists('app_items', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'lines.*.measurement_unit_id' => [
                'required',
                'uuid',
                Rule::exists('app_measurement_units', 'id')->where('company_id', $companyId),
            ],
            /** A qué bodega reingresa esta línea; nace con la de la cabecera. */
            'lines.*.warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'lines.*.sales_invoice_line_id' => ['nullable', 'uuid'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.reason' => ['nullable', 'string', Rule::in(SalesReturn::REASONS)],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function salesReturnMessages(): array
    {
        return [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no está disponible.',
            'sales_invoice_id.exists' => 'La factura indicada no existe en esta empresa.',
            'warehouse_id.required' => 'Indica la bodega a la que reingresa la mercancía.',
            'warehouse_id.exists' => 'La bodega seleccionada no está disponible.',
            'return_date.required' => 'La fecha de la devolución es obligatoria.',
            'reason.required' => 'El motivo de la devolución es obligatorio.',
            'reason.in' => 'El motivo indicado no existe.',
            'reason_detail.required' => 'Explica el motivo cuando eliges «Otro».',
            'condition.required' => 'Indica en qué estado vuelve la mercancía.',
            'condition.in' => 'La condición indicada no existe.',
            'currency.required' => 'La moneda de la devolución es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'received_by.exists' => 'El usuario que recibe la mercancía no existe.',
            'lines.required' => 'La devolución debe tener al menos una línea.',
            'lines.min' => 'La devolución debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.warehouse_id.required' => 'Indica la bodega a la que reingresa la línea.',
            'lines.*.warehouse_id.exists' => 'La bodega de la línea no está disponible.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateSalesReturnInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'La devolución debe tener al menos una línea activa.');

            return;
        }

        $this->validateLineUnits($validator, $lines);
        $this->validateReturnedLines($validator, $lines);
        $this->validateDestinationWarehouse($validator, $lines);
    }

    /**
     * La unidad de la línea debe ser una de las unidades activas del artículo:
     * de ahí sale el factor con que se convierte a la unidad base.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateLineUnits(Validator $validator, array $lines): void
    {
        $itemIds = array_values(array_filter(array_column($lines, 'item_id')));

        if ($itemIds === []) {
            return;
        }

        $pairs = ItemUnit::query()
            ->whereIn('item_id', $itemIds)
            ->where('status', 'active')
            ->get(['item_id', 'measurement_unit_id'])
            ->map(fn (ItemUnit $unit): string => $unit->item_id.'|'.$unit->measurement_unit_id)
            ->all();

        foreach ($lines as $index => $line) {
            $itemId = $line['item_id'] ?? null;
            $unitId = $line['measurement_unit_id'] ?? null;

            if ($itemId === null || $unitId === null) {
                continue;
            }

            if (! in_array($itemId.'|'.$unitId, $pairs, true)) {
                $validator->errors()->add(
                    "lines.{$index}.measurement_unit_id",
                    'La unidad seleccionada no está registrada para ese artículo.',
                );
            }
        }
    }

    /**
     * Una línea no puede devolver una línea de factura si la devolución no dice
     * a qué factura pertenece: sin cabecera no hay contra qué comprobarla.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateReturnedLines(Validator $validator, array $lines): void
    {
        if (filled($this->input('sales_invoice_id'))) {
            return;
        }

        foreach ($lines as $index => $line) {
            if (filled($line['sales_invoice_line_id'] ?? null)) {
                $validator->errors()->add(
                    "lines.{$index}.sales_invoice_line_id",
                    'Elige la factura de origen antes de devolver una de sus líneas.',
                );
            }
        }
    }

    /**
     * La condición decide a qué bodega vuelve la mercancía: lo dañado va a
     * cuarentena y lo revendible no, para que no se mezcle con lo que sí se
     * puede vender. Con `scrap` nada reingresa, así que la bodega da igual.
     *
     * La comprobación cubre la bodega de la cabecera y la de cada línea: la
     * línea puede apartarse de la cabecera, pero no de la condición.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateDestinationWarehouse(Validator $validator, array $lines): void
    {
        $condition = (string) $this->input('condition');

        if ($condition === SalesReturn::SCRAP_CONDITION) {
            return;
        }

        $fields = ['warehouse_id' => (string) $this->input('warehouse_id')];

        foreach ($lines as $index => $line) {
            if (filled($line['warehouse_id'] ?? null)) {
                $fields["lines.{$index}.warehouse_id"] = (string) $line['warehouse_id'];
            }
        }

        $types = Warehouse::query()
            ->whereIn('id', array_values(array_unique(array_filter($fields))))
            ->pluck('type', 'id')
            ->all();

        foreach ($fields as $field => $warehouseId) {
            $type = $types[$warehouseId] ?? null;

            if ($type === null) {
                continue;
            }

            $isQuarantine = $type === SalesReturn::QUARANTINE_WAREHOUSE_TYPE;

            if ($condition === SalesReturn::QUARANTINE_CONDITION && ! $isQuarantine) {
                $validator->errors()->add(
                    $field,
                    'La mercancía dañada reingresa a una bodega de cuarentena.',
                );

                continue;
            }

            if ($condition !== SalesReturn::QUARANTINE_CONDITION && $isQuarantine) {
                $validator->errors()->add(
                    $field,
                    'Una bodega de cuarentena solo recibe mercancía dañada.',
                );
            }
        }
    }
}
