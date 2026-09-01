<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Requests\Concerns;

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera del ajuste más sus líneas.
 *
 * De la línea solo se valida **lo contado**. Ni la existencia del sistema, ni
 * la diferencia, ni la cantidad en unidad base, ni la dirección del movimiento
 * se capturan: las resuelve el backend leyendo el inventario, que es lo único
 * que hace que un ajuste pruebe algo. El costo tampoco, salvo en una
 * revaluación, donde el costo nuevo es justamente lo que el documento decide.
 *
 * Lo que no cabe aquí porque exige haber resuelto la existencia —que la
 * dirección declarada sea la que las líneas producen, que el lote y la serie
 * sean de artículos que los llevan, que una revaluación tenga qué revaluar— lo
 * comprueba `AdjustmentLimitsService` antes de guardar.
 */
trait ValidatesAdjustmentPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function adjustmentRules(): array
    {
        $companyId = session('current_company_id');

        return [
            /** La bodega es de la cabecera: un ajuste corrige una sola bodega. */
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'adjustment_date' => ['required', 'date'],
            'type' => ['required', 'string', Rule::in(Adjustment::TYPES)],
            'direction' => ['required', 'string', Rule::in(Adjustment::DIRECTIONS)],
            /** Justificación obligatoria: un ajuste sin motivo no se registra. */
            'reason' => ['required', 'string', 'max:500'],
            'count_id' => ['nullable', 'string', 'max:60'],
            'attachment_path' => ['nullable', 'string', 'max:500'],
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
            'lines.*.location_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouse_locations', 'id')->where('company_id', $companyId),
            ],
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
            /** Lo que se encontró al contar. Cero es una respuesta válida. */
            'lines.*.counted_quantity' => ['required', 'numeric', 'min:0'],
            /** Solo lo lee una revaluación: en el resto el costo lo pone el kardex. */
            'lines.*.unit_cost' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.reason' => ['nullable', 'string', 'max:500'],
            'lines.*.counted_by' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function adjustmentMessages(): array
    {
        return [
            'warehouse_id.required' => 'Indica la bodega que se ajusta.',
            'warehouse_id.exists' => 'La bodega seleccionada no está disponible.',
            'adjustment_date.required' => 'La fecha del ajuste es obligatoria.',
            'type.required' => 'El tipo de ajuste es obligatorio.',
            'type.in' => 'El tipo de ajuste indicado no existe.',
            'direction.required' => 'Indica si el ajuste suma, resta o hace ambas cosas.',
            'direction.in' => 'La dirección indicada no existe.',
            'reason.required' => 'El motivo del ajuste es obligatorio.',
            'reason.max' => 'El motivo no puede pasar de 500 caracteres.',
            'lines.required' => 'El ajuste debe tener al menos una línea.',
            'lines.min' => 'El ajuste debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.counted_quantity.required' => 'Indica la cantidad contada.',
            'lines.*.counted_quantity.min' => 'La cantidad contada no puede ser negativa.',
            'lines.*.unit_cost.gt' => 'El costo nuevo debe ser mayor que cero.',
            'lines.*.lot_id.exists' => 'El lote de la línea no existe en esta empresa.',
            'lines.*.serial_id.exists' => 'La serie de la línea no existe en esta empresa.',
            'lines.*.location_id.exists' => 'La ubicación de la línea no existe en esta empresa.',
            'lines.*.counted_by.exists' => 'El usuario que contó la línea no existe.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateAdjustmentInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'El ajuste debe tener al menos una línea activa.');

            return;
        }

        $this->validateLineUnits($validator, $lines);
        $this->validateLineLocations($validator, $lines);
        $this->validateUniqueLines($validator, $lines);
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
     * La ubicación de la línea, si se indica, tiene que ser de la bodega de la
     * cabecera: se cuenta un sitio concreto de esa bodega.
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
                    'La ubicación no pertenece a la bodega del ajuste.',
                );
            }
        }
    }

    /**
     * Dos líneas activas no pueden contar lo mismo. Cada una se compara contra
     * la existencia de su clave —artículo, ubicación, lote y serie—, así que
     * repetirla aplicaría la misma diferencia dos veces.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateUniqueLines(Validator $validator, array $lines): void
    {
        $seen = [];

        foreach ($lines as $index => $line) {
            if (($line['status'] ?? 'active') !== 'active' || blank($line['item_id'] ?? null)) {
                continue;
            }

            $key = implode('|', [
                (string) $line['item_id'],
                (string) ($line['location_id'] ?? ''),
                (string) ($line['lot_id'] ?? ''),
                (string) ($line['serial_id'] ?? ''),
            ]);

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "lines.{$index}.item_id",
                    'Esa misma existencia ya se cuenta en otra línea del ajuste.',
                );

                continue;
            }

            $seen[$key] = $index;
        }
    }
}
