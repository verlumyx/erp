<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\Item\Models\ItemUnit;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la orden más sus líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`, los avances y los porcentajes
 * de recepción) no se validan porque no se capturan: los calcula el backend a
 * partir de cantidad, precio y porcentajes.
 */
trait ValidatesPurchaseOrderPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function purchaseOrderRules(): array
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
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'supplier_reference' => ['nullable', 'string', 'max:60'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
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
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', 'uuid'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.withholding_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function purchaseOrderMessages(): array
    {
        return [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no está disponible.',
            'warehouse_id.required' => 'La bodega de recepción es obligatoria.',
            'warehouse_id.exists' => 'La bodega seleccionada no está disponible.',
            'order_date.required' => 'La fecha de emisión es obligatoria.',
            'expected_date.after_or_equal' => 'La fecha estimada de entrega no puede ser anterior a la de emisión.',
            'currency.required' => 'La moneda de la orden es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'lines.required' => 'La orden debe tener al menos una línea.',
            'lines.min' => 'La orden debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.unit_price.min' => 'El costo unitario no puede ser negativo.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validatePurchaseOrderInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'La orden debe tener al menos una línea activa.');

            return;
        }

        $this->validateLineUnits($validator, $lines);
        $this->validateGlobalDiscount($validator, $active);
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
