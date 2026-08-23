<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la devolución más sus
 * líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`) no se validan porque no se
 * capturan: los calcula el backend a partir de cantidad, precio y porcentajes.
 *
 * Lo que no cabe aquí porque exige leer la factura —que las líneas devueltas
 * sean suyas, que no se devuelva más de lo facturado y que el lote sea el
 * recibido— lo comprueba `PurchaseReturnLimitsService` antes de guardar.
 */
trait ValidatesPurchaseReturnPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function purchaseReturnRules(): array
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
            /** Vacía cuando la mercancía se devuelve sin factura de por medio. */
            'purchase_invoice_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_purchase_invoices', 'id')->where('company_id', $companyId),
            ],
            /** Sin regla `exists`: el módulo Entradas de Logística aún no existe. */
            'entry_id' => ['nullable', 'uuid'],
            /** La bodega es de la cabecera: toda la devolución sale del mismo sitio. */
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', Rule::in(PurchaseReturn::REASONS)],
            /** Obligatorio cuando el motivo es `other`: si no, no se sabe por qué. */
            'reason_detail' => [
                Rule::requiredIf(fn (): bool => $this->input('reason') === 'other'),
                'nullable',
                'string',
                'max:500',
            ],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'carrier' => ['nullable', 'string', 'max:150'],
            'tracking_number' => ['nullable', 'string', 'max:60'],
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
            'lines.*.purchase_invoice_line_id' => ['nullable', 'uuid'],
            'lines.*.lot_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_item_lots', 'id')->where('company_id', $companyId),
            ],
            'lines.*.serial_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_item_serials', 'id')->where('company_id', $companyId),
            ],
            'lines.*.location_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouse_locations', 'id')->where('company_id', $companyId),
            ],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_taxes', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.withholding_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.reason' => ['nullable', 'string', Rule::in(PurchaseReturn::REASONS)],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function purchaseReturnMessages(): array
    {
        return [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no está disponible.',
            'purchase_invoice_id.exists' => 'La factura indicada no existe en esta empresa.',
            'warehouse_id.required' => 'Indica la bodega desde la que sale la mercancía.',
            'warehouse_id.exists' => 'La bodega seleccionada no está disponible.',
            'return_date.required' => 'La fecha de la devolución es obligatoria.',
            'reason.required' => 'El motivo de la devolución es obligatorio.',
            'reason.in' => 'El motivo indicado no existe.',
            'reason_detail.required' => 'Explica el motivo cuando eliges «Otro».',
            'currency.required' => 'La moneda de la devolución es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'lines.required' => 'La devolución debe tener al menos una línea.',
            'lines.min' => 'La devolución debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'lines.*.tax_id.exists' => 'El impuesto de la línea no existe o está inactivo.',
            'lines.*.lot_id.exists' => 'El lote de la línea no existe en esta empresa.',
            'lines.*.serial_id.exists' => 'La serie de la línea no existe en esta empresa.',
            'lines.*.location_id.exists' => 'La ubicación de la línea no existe en esta empresa.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validatePurchaseReturnInvariants(Validator $validator): void
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
        $this->validateLineLocations($validator, $lines);
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
        if (filled($this->input('purchase_invoice_id'))) {
            return;
        }

        foreach ($lines as $index => $line) {
            if (filled($line['purchase_invoice_line_id'] ?? null)) {
                $validator->errors()->add(
                    "lines.{$index}.purchase_invoice_line_id",
                    'Elige la factura de origen antes de devolver una de sus líneas.',
                );
            }
        }
    }

    /**
     * La ubicación de la línea, si se indica, tiene que ser de la bodega de la
     * cabecera: la mercancía sale de un sitio concreto de esa bodega.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateLineLocations(Validator $validator, array $lines): void
    {
        $locationIds = array_values(array_filter(array_column($lines, 'location_id')));

        if ($locationIds === []) {
            return;
        }

        $warehouseOf = WarehouseLocation::query()
            ->whereIn('id', $locationIds)
            ->pluck('warehouse_id', 'id')
            ->all();

        $warehouseId = (string) $this->input('warehouse_id');

        foreach ($lines as $index => $line) {
            $locationId = $line['location_id'] ?? null;

            if (blank($locationId)) {
                continue;
            }

            if (($warehouseOf[$locationId] ?? null) !== $warehouseId) {
                $validator->errors()->add(
                    "lines.{$index}.location_id",
                    'La ubicación no pertenece a la bodega de la devolución.',
                );
            }
        }
    }
}
