<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Requests\Concerns;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera del despacho más sus
 * líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`) no se validan porque no se
 * capturan: los calcula el backend a partir de cantidad, precio y porcentajes.
 * Los totales de la cabecera tampoco: salen de las líneas y del artículo. El
 * costo de salida menos aún: lo pone el kardex.
 *
 * Lo que no cabe aquí porque exige leer el pedido —que sea del mismo cliente,
 * que las líneas despachadas sean suyas y que no se saque más de lo pedido— lo
 * comprueba `DispatchSourceService` antes de guardar.
 */
trait ValidatesDispatchPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function dispatchRules(): array
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
            /** Vacío en un despacho directo, sin pedido previo. */
            'sourceable_type' => [
                'nullable',
                'string',
                Rule::in(Dispatch::SOURCE_TYPES),
                'required_with:sourceable_id',
            ],
            'sourceable_id' => ['nullable', 'uuid', 'required_with:sourceable_type'],
            'client_address_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_client_addresses', 'id')->where('company_id', $companyId),
            ],
            /** La bodega es de la cabecera: todo el despacho sale del mismo sitio. */
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            /** La ruta por la que sale: solo una activa de la misma empresa. */
            'route_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_routes', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            /**
             * La parada la escribe la planificación de la ruta, no esta
             * pantalla; si viene, al menos tiene que ser de esta empresa.
             */
            'route_stop_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_route_stops', 'id')->where('company_id', $companyId),
            ],
            'dispatch_date' => ['required', 'date'],
            'driver_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'carrier' => ['nullable', 'string', 'max:150'],
            'tracking_number' => ['nullable', 'string', 'max:60'],
            'freight_amount' => ['nullable', 'numeric', 'min:0'],
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
            'lines.*.sourceable_type' => [
                'nullable',
                'string',
                Rule::in(Dispatch::SOURCE_LINE_TYPES),
                'required_with:lines.*.sourceable_id',
            ],
            'lines.*.sourceable_id' => ['nullable', 'uuid', 'required_with:lines.*.sourceable_type'],
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
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
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
    protected function dispatchMessages(): array
    {
        return [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no está disponible.',
            'sourceable_type.in' => 'Ese tipo de documento origen no es válido para un despacho.',
            'sourceable_type.required_with' => 'Indica el tipo del documento origen.',
            'sourceable_id.required_with' => 'Indica el documento origen.',
            'client_address_id.exists' => 'La dirección de entrega no es de esta empresa.',
            'warehouse_id.required' => 'Indica la bodega de la que sale la mercancía.',
            'warehouse_id.exists' => 'La bodega seleccionada no está disponible.',
            'dispatch_date.required' => 'La fecha de salida es obligatoria.',
            'driver_id.exists' => 'El conductor indicado no existe.',
            'freight_amount.min' => 'El costo del flete no puede ser negativo.',
            'lines.required' => 'El despacho debe tener al menos una línea.',
            'lines.min' => 'El despacho debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.sourceable_type.in' => 'Ese tipo de línea origen no es válido para un despacho.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.tax_id.exists' => 'El impuesto de la línea no existe o está inactivo.',
            'lines.*.lot_id.exists' => 'El lote de la línea no existe en esta empresa.',
            'lines.*.serial_id.exists' => 'La serie de la línea no existe en esta empresa.',
            'lines.*.location_id.exists' => 'La ubicación de la línea no existe en esta empresa.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateDispatchInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'El despacho debe tener al menos una línea activa.');

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
                    'La ubicación no pertenece a la bodega del despacho.',
                );
            }
        }
    }
}
