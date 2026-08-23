<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la nota de crédito más
 * sus líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`, `balance` y los `_ves`) no
 * se validan porque no se capturan: los calcula el backend a partir de
 * cantidad, precio y porcentajes.
 *
 * Lo que no cabe aquí porque exige leer la factura —que las líneas acreditadas
 * sean suyas y que no se acredite más de lo facturado— lo comprueba
 * `PurchaseCreditNoteLimitsService` antes de guardar.
 */
trait ValidatesPurchaseCreditNotePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function purchaseCreditNoteRules(): array
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
            /** Vacía cuando la nota no corrige una factura concreta. */
            'purchase_invoice_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_purchase_invoices', 'id')->where('company_id', $companyId),
            ],
            /** Devolución que origina la nota, cuando la nota nace de una. */
            'purchase_return_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_purchase_returns', 'id')->where('company_id', $companyId),
            ],
            'supplier_document_number' => ['nullable', 'string', 'max:60'],
            'note_date' => ['required', 'date'],
            'reason' => ['required', 'string', Rule::in(PurchaseCreditNote::REASONS)],
            /** Obligatorio cuando el motivo es `other`: si no, no se sabe por qué. */
            'reason_detail' => [
                Rule::requiredIf(fn (): bool => $this->input('reason') === 'other'),
                'nullable',
                'string',
                'max:500',
            ],
            'affects_inventory' => ['nullable', 'string', 'in:yes,no'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
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
            'lines.*.warehouse_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'lines.*.lot_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_item_lots', 'id')->where('company_id', $companyId),
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
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function purchaseCreditNoteMessages(): array
    {
        return [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no está disponible.',
            'purchase_invoice_id.exists' => 'La factura indicada no existe en esta empresa.',
            'note_date.required' => 'La fecha de la nota es obligatoria.',
            'reason.required' => 'El motivo de la nota es obligatorio.',
            'reason.in' => 'El motivo indicado no existe.',
            'reason_detail.required' => 'Explica el motivo cuando eliges «Otro».',
            'currency.required' => 'La moneda de la nota es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'lines.required' => 'La nota debe tener al menos una línea.',
            'lines.min' => 'La nota debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'lines.*.tax_id.exists' => 'El impuesto de la línea no existe o está inactivo.',
            'lines.*.lot_id.exists' => 'El lote de la línea no existe en esta empresa.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validatePurchaseCreditNoteInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'La nota debe tener al menos una línea activa.');

            return;
        }

        $this->validateLineUnits($validator, $lines);
        $this->validateCreditedLines($validator, $lines);
        $this->validateInventoryWarehouses($validator, $lines);
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
     * Una línea no puede acreditar una línea de factura si la nota no dice a
     * qué factura pertenece: sin cabecera no hay contra qué comprobarla.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateCreditedLines(Validator $validator, array $lines): void
    {
        if (filled($this->input('purchase_invoice_id'))) {
            return;
        }

        foreach ($lines as $index => $line) {
            if (filled($line['purchase_invoice_line_id'] ?? null)) {
                $validator->errors()->add(
                    "lines.{$index}.purchase_invoice_line_id",
                    'Elige la factura afectada antes de acreditar una de sus líneas.',
                );
            }
        }
    }

    /**
     * Si la nota saca mercancía del inventario, cada línea activa tiene que
     * decir de qué bodega sale: la cabecera no lleva bodega propia.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateInventoryWarehouses(Validator $validator, array $lines): void
    {
        if ($this->input('affects_inventory', 'no') !== 'yes') {
            return;
        }

        foreach ($lines as $index => $line) {
            if (($line['status'] ?? 'active') !== 'active') {
                continue;
            }

            if (blank($line['warehouse_id'] ?? null)) {
                $validator->errors()->add(
                    "lines.{$index}.warehouse_id",
                    'Indica la bodega: la nota saca mercancía del inventario.',
                );
            }
        }
    }
}
