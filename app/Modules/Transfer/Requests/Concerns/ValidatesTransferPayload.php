<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Requests\Concerns;

use App\Modules\Item\Models\ItemUnit;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera del traslado más sus
 * líneas.
 *
 * No hay nada de dinero que validar: el traslado no pone precio, no lleva
 * descuento y no grava impuesto. Lo único que vale es el costo con el que la
 * mercancía viaja, y ese lo pone el kardex al sacarla del origen.
 */
trait ValidatesTransferPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function transferRules(): array
    {
        $companyId = session('current_company_id');

        $activeWarehouse = fn (): Exists => Rule::exists('app_warehouses', 'id')
            ->where('company_id', $companyId)
            ->where('status', 'active');

        return [
            'origin_warehouse_id' => ['required', 'uuid', $activeWarehouse()],
            /** Origen y destino no pueden ser la misma bodega: no sería un traslado. */
            'destination_warehouse_id' => [
                'required',
                'uuid',
                'different:origin_warehouse_id',
                $activeWarehouse(),
            ],
            /**
             * Su presencia decide el número de pasos, así que no puede ser
             * ninguna de las otras dos: la mercancía tiene que poder estar en
             * ella sin estar ya en el origen ni en el destino.
             */
            'transit_warehouse_id' => [
                'nullable',
                'uuid',
                'different:origin_warehouse_id',
                'different:destination_warehouse_id',
                $activeWarehouse(),
            ],
            'transfer_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:transfer_date'],
            'reason' => ['required', 'string', Rule::in(Transfer::REASONS)],
            /** «Otro» sin explicación no dice nada: se exige el detalle. */
            'reason_detail' => ['nullable', 'string', 'max:500', 'required_if:reason,other'],
            'driver_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            /** La ruta del viaje: solo una activa de la misma empresa. */
            'route_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_routes', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
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
            'lines.*.origin_location_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouse_locations', 'id')->where('company_id', $companyId),
            ],
            'lines.*.destination_location_id' => [
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
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function transferMessages(): array
    {
        return [
            'origin_warehouse_id.required' => 'Indica la bodega de la que sale la mercancía.',
            'origin_warehouse_id.exists' => 'La bodega de origen no está disponible.',
            'destination_warehouse_id.required' => 'Indica la bodega a la que llega la mercancía.',
            'destination_warehouse_id.different' => 'El origen y el destino no pueden ser la misma bodega.',
            'destination_warehouse_id.exists' => 'La bodega de destino no está disponible.',
            'transit_warehouse_id.different' => 'La bodega de tránsito tiene que ser distinta del origen y del destino.',
            'transit_warehouse_id.exists' => 'La bodega de tránsito no está disponible.',
            'transfer_date.required' => 'La fecha de salida es obligatoria.',
            'expected_date.after_or_equal' => 'La mercancía no puede llegar antes de salir.',
            'reason.required' => 'Indica por qué se traslada la mercancía.',
            'reason.in' => 'Ese motivo de traslado no existe.',
            'reason_detail.required_if' => 'Explica el motivo del traslado.',
            'driver_id.exists' => 'El conductor indicado no existe.',
            'lines.required' => 'El traslado debe tener al menos una línea.',
            'lines.min' => 'El traslado debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.lot_id.exists' => 'El lote de la línea no existe en esta empresa.',
            'lines.*.serial_id.exists' => 'La serie de la línea no existe en esta empresa.',
            'lines.*.origin_location_id.exists' => 'La ubicación de origen no existe en esta empresa.',
            'lines.*.destination_location_id.exists' => 'La ubicación de destino no existe en esta empresa.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateTransferInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'El traslado debe tener al menos una línea activa.');

            return;
        }

        $this->validateLineUnits($validator, $lines);
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
     * Cada ubicación de la línea, si se indica, tiene que ser de su bodega: la
     * de origen sale de la bodega de origen y la de destino guarda en la de
     * destino.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateLineLocations(Validator $validator, array $lines): void
    {
        $locationIds = array_values(array_filter([
            ...array_column($lines, 'origin_location_id'),
            ...array_column($lines, 'destination_location_id'),
        ]));

        if ($locationIds === []) {
            return;
        }

        $warehouseOf = WarehouseLocation::query()
            ->whereIn('id', $locationIds)
            ->pluck('warehouse_id', 'id')
            ->all();

        $expected = [
            'origin_location_id' => [
                (string) $this->input('origin_warehouse_id'),
                'La ubicación no pertenece a la bodega de origen.',
            ],
            'destination_location_id' => [
                (string) $this->input('destination_warehouse_id'),
                'La ubicación no pertenece a la bodega de destino.',
            ],
        ];

        foreach ($lines as $index => $line) {
            foreach ($expected as $field => [$warehouseId, $message]) {
                $locationId = $line[$field] ?? null;

                if (blank($locationId)) {
                    continue;
                }

                if (($warehouseOf[$locationId] ?? null) !== $warehouseId) {
                    $validator->errors()->add("lines.{$index}.{$field}", $message);
                }
            }
        }
    }
}
