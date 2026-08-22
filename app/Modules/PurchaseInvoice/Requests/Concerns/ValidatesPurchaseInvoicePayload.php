<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la factura más sus
 * líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`, `balance`, los `_ves` y el
 * costo con flete prorrateado) no se validan porque no se capturan: los calcula
 * el backend a partir de cantidad, costo y porcentajes.
 */
trait ValidatesPurchaseInvoicePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function purchaseInvoiceRules(): array
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
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            /**
             * El número impreso por el proveedor no se repite dentro de la
             * empresa: es lo que bloquea registrar dos veces la misma factura.
             */
            'supplier_invoice_number' => [
                'required',
                'string',
                'max:60',
                Rule::unique('app_purchase_invoices', 'supplier_invoice_number')
                    ->where('company_id', $companyId)
                    ->where('supplier_id', $this->input('supplier_id'))
                    ->ignore($this->route('id')),
            ],
            'supplier_invoice_series' => ['nullable', 'string', 'max:20'],
            'invoice_date' => ['required', 'date'],
            'received_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            /** Vacía la deriva el backend con los días de crédito del proveedor. */
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'affects_inventory' => ['nullable', 'string', 'in:yes,no'],

            /** Documento origen: el alias tiene que estar en el morph map. */
            'sourceable_type' => ['nullable', 'string', Rule::in(PurchaseInvoice::SOURCE_TYPES)],
            'sourceable_id' => ['nullable', 'uuid'],
            'entry_id' => ['nullable', 'uuid'],

            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'freight_amount' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
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
            'lines.*.warehouse_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'lines.*.lot_id' => ['nullable', 'uuid'],
            'lines.*.sourceable_type' => ['nullable', 'string', Rule::in(PurchaseInvoiceLine::SOURCE_TYPES)],
            'lines.*.sourceable_id' => ['nullable', 'uuid'],
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
    protected function purchaseInvoiceMessages(): array
    {
        return [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no está disponible.',
            'warehouse_id.required' => 'La bodega es obligatoria.',
            'warehouse_id.exists' => 'La bodega seleccionada no está disponible.',
            'supplier_invoice_number.required' => 'El número de la factura del proveedor es obligatorio.',
            'supplier_invoice_number.unique' => 'Ese proveedor ya tiene registrada una factura con ese número.',
            'invoice_date.required' => 'La fecha de emisión es obligatoria.',
            'received_date.after_or_equal' => 'La fecha de recepción no puede ser anterior a la de emisión.',
            'due_date.after_or_equal' => 'El vencimiento no puede ser anterior a la fecha de emisión.',
            'currency.required' => 'La moneda de la factura es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'sourceable_type.in' => 'El tipo de documento origen no está admitido.',
            'lines.required' => 'La factura debe tener al menos una línea.',
            'lines.min' => 'La factura debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.unit_price.min' => 'El costo unitario no puede ser negativo.',
            'lines.*.tax_id.exists' => 'El impuesto de la línea no existe o está inactivo.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validatePurchaseInvoiceInvariants(Validator $validator): void
    {
        $this->validateSourcePair($validator);

        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'La factura debe tener al menos una línea activa.');

            return;
        }

        $this->validateLineUnits($validator, $lines);
        $this->validateGlobalDiscount($validator, $active);
    }

    /**
     * El documento origen son dos columnas que viajan juntas: una factura
     * directa las deja vacías, y una que nace de una orden trae las dos.
     */
    private function validateSourcePair(Validator $validator): void
    {
        $type = $this->input('sourceable_type');
        $id = $this->input('sourceable_id');

        if (filled($type) && blank($id)) {
            $validator->errors()->add('sourceable_id', 'Falta el documento origen de la factura.');
        }

        if (blank($type) && filled($id)) {
            $validator->errors()->add('sourceable_type', 'Falta el tipo del documento origen.');
        }

        foreach ($this->input('lines', []) as $index => $line) {
            $lineType = $line['sourceable_type'] ?? null;
            $lineId = $line['sourceable_id'] ?? null;

            if (filled($lineType) && blank($lineId)) {
                $validator->errors()->add("lines.{$index}.sourceable_id", 'Falta la línea origen.');
            }

            if (blank($lineType) && filled($lineId)) {
                $validator->errors()->add("lines.{$index}.sourceable_type", 'Falta el tipo de la línea origen.');
            }
        }
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
     * El descuento global es una rebaja sobre el documento: no puede dejar el
     * subtotal en negativo.
     *
     * @param  array<int, array<string, mixed>>  $activeLines
     */
    private function validateGlobalDiscount(Validator $validator, array $activeLines): void
    {
        $discount = (float) $this->input('discount_amount', 0);

        if ($discount <= 0) {
            return;
        }

        $subtotal = 0.0;

        foreach ($activeLines as $line) {
            $gross = (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0);
            $percent = (float) ($line['discount_percent'] ?? 0);

            $subtotal += $percent > 0
                ? round($gross - round($gross * $percent / 100, 2), 2)
                : round($gross - round((float) ($line['discount_amount'] ?? 0), 2), 2);
        }

        if ($discount > round($subtotal, 2)) {
            $validator->errors()->add(
                'discount_amount',
                'El descuento global no puede superar el subtotal de las líneas.',
            );
        }
    }
}
