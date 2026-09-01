<?php

declare(strict_types=1);

namespace App\Modules\Entry\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera de la entrada más sus
 * líneas.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`) no se validan porque no se
 * capturan: los calcula el backend a partir de cantidad, costo y porcentajes.
 * Tampoco la cantidad aceptada —es lo que llegó menos lo rechazado— ni el costo
 * con el que la mercancía entra al kardex, que sale de prorratear el flete.
 *
 * Lo que no cabe aquí porque exige leer la orden de compra o el maestro de
 * artículos —que la orden sea del mismo proveedor, que no se reciba más de lo
 * pedido, que el lote y las series correspondan a artículos que los admiten—
 * lo comprueba `EntryLimitsService` antes de guardar.
 */
trait ValidatesEntryPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function entryRules(): array
    {
        $companyId = session('current_company_id');

        return [
            /** Vacío cuando la mercancía no viene de un proveedor. */
            'supplier_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_suppliers', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'sourceable_type' => ['nullable', 'string', Rule::in(Entry::SOURCE_TYPES)],
            'sourceable_id' => ['nullable', 'uuid'],
            /** La bodega es de la cabecera: toda la entrada llega al mismo sitio. */
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'entry_date' => ['required', 'date'],
            'entry_type' => ['required', 'string', Rule::in(Entry::TYPES)],
            'supplier_document' => ['nullable', 'string', 'max:60'],
            'carrier' => ['nullable', 'string', 'max:150'],
            'tracking_number' => ['nullable', 'string', 'max:60'],
            'received_by' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'inspected_by' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'inspection_status' => ['required', 'string', Rule::in(Entry::INSPECTION_STATUSES)],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            /** Gastos capitalizables: se prorratean al costo, no se facturan aparte. */
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
            'lines.*.sourceable_type' => ['nullable', 'string', Rule::in(EntryLine::SOURCE_TYPES)],
            'lines.*.sourceable_id' => ['nullable', 'uuid'],
            'lines.*.location_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouse_locations', 'id')->where('company_id', $companyId),
            ],
            'lines.*.lot_number' => ['nullable', 'string', 'max:60'],
            'lines.*.lot_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_item_lots', 'id')->where('company_id', $companyId),
            ],
            'lines.*.expires_at' => ['nullable', 'date'],
            'lines.*.serial_numbers' => ['nullable', 'array'],
            'lines.*.serial_numbers.*' => ['string', 'max:60'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.rejected_quantity' => ['nullable', 'numeric', 'min:0'],
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
            'lines.*.rejection_reason' => ['nullable', 'string', 'max:500'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function entryMessages(): array
    {
        return [
            'supplier_id.exists' => 'El proveedor seleccionado no está disponible.',
            'sourceable_type.in' => 'El tipo de documento origen no está admitido.',
            'warehouse_id.required' => 'Indica la bodega que recibe la mercancía.',
            'warehouse_id.exists' => 'La bodega seleccionada no está disponible.',
            'entry_date.required' => 'La fecha de la entrada es obligatoria.',
            'entry_type.required' => 'El tipo de entrada es obligatorio.',
            'entry_type.in' => 'El tipo de entrada indicado no existe.',
            'inspection_status.required' => 'Indica el resultado del control de calidad.',
            'inspection_status.in' => 'El resultado del control de calidad no existe.',
            'received_by.exists' => 'El usuario que recibe la mercancía no existe.',
            'inspected_by.exists' => 'El usuario que inspecciona la mercancía no existe.',
            'currency.required' => 'La moneda de la entrada es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'freight_amount.min' => 'El flete no puede ser negativo.',
            'other_charges.min' => 'Los otros gastos no pueden ser negativos.',
            'lines.required' => 'La entrada debe tener al menos una línea.',
            'lines.min' => 'La entrada debe tener al menos una línea.',
            'lines.*.item_id.required' => 'Selecciona el artículo de la línea.',
            'lines.*.item_id.exists' => 'El artículo seleccionado no está disponible.',
            'lines.*.measurement_unit_id.required' => 'Selecciona la unidad de la línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.rejected_quantity.min' => 'La cantidad rechazada no puede ser negativa.',
            'lines.*.unit_price.min' => 'El costo unitario no puede ser negativo.',
            'lines.*.tax_id.exists' => 'El impuesto de la línea no existe o está inactivo.',
            'lines.*.lot_id.exists' => 'El lote de la línea no existe en esta empresa.',
            'lines.*.location_id.exists' => 'La ubicación de la línea no existe en esta empresa.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateEntryInvariants(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);

        $active = array_filter(
            $lines,
            static fn (array $line): bool => ($line['status'] ?? 'active') === 'active',
        );

        if ($active === []) {
            $validator->errors()->add('lines', 'La entrada debe tener al menos una línea activa.');

            return;
        }

        $this->validateLineUnits($validator, $lines);
        $this->validateRejections($validator, $lines);
        $this->validateLineLocations($validator, $lines);
        $this->validateSourcePair($validator, $lines);
        $this->validateEntryType($validator);
        $this->validateInspection($validator, $active);
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
     * No se puede rechazar más de lo que llegó, y lo que se rechaza se explica:
     * ese es el papel con el que se le reclama al proveedor.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateRejections(Validator $validator, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $quantity = round((float) ($line['quantity'] ?? 0), 4);
            $rejected = round((float) ($line['rejected_quantity'] ?? 0), 4);

            if ($rejected > $quantity) {
                $validator->errors()->add(
                    "lines.{$index}.rejected_quantity",
                    'No se puede rechazar más de lo que llegó en la línea.',
                );

                continue;
            }

            if ($rejected > 0 && blank($line['rejection_reason'] ?? null)) {
                $validator->errors()->add(
                    "lines.{$index}.rejection_reason",
                    'Explica por qué se rechaza esa cantidad.',
                );
            }
        }
    }

    /**
     * La ubicación de la línea, si se indica, tiene que ser de la bodega de la
     * cabecera: la mercancía se guarda en un sitio concreto de esa bodega.
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
                    'La ubicación no pertenece a la bodega de la entrada.',
                );
            }
        }
    }

    /**
     * El documento origen son dos columnas que viajan juntas: una entrada sin
     * documento previo las deja vacías, y una que nace de una orden trae las
     * dos. Una línea no puede apuntar a la línea de una orden si la entrada no
     * dice de qué orden sale: sin cabecera no hay contra qué comprobarla.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateSourcePair(Validator $validator, array $lines): void
    {
        $type = $this->input('sourceable_type');
        $id = $this->input('sourceable_id');

        if (filled($type) && blank($id)) {
            $validator->errors()->add('sourceable_id', 'Falta el documento origen de la entrada.');
        }

        if (blank($type) && filled($id)) {
            $validator->errors()->add('sourceable_type', 'Falta el tipo del documento origen.');
        }

        foreach ($lines as $index => $line) {
            $lineType = $line['sourceable_type'] ?? null;
            $lineId = $line['sourceable_id'] ?? null;

            if (filled($lineType) && blank($lineId)) {
                $validator->errors()->add("lines.{$index}.sourceable_id", 'Falta la línea origen.');
            }

            if (blank($lineType) && filled($lineId)) {
                $validator->errors()->add("lines.{$index}.sourceable_type", 'Falta el tipo de la línea origen.');
            }

            if (filled($lineId) && blank($id)) {
                $validator->errors()->add(
                    "lines.{$index}.sourceable_id",
                    'Elige la orden de compra de origen antes de recibir una de sus líneas.',
                );
            }
        }
    }

    /**
     * El tipo de entrada decide de dónde puede venir la mercancía: lo que se
     * compra viene de un proveedor, y el inventario inicial no viene de nadie
     * —es el saldo con el que la empresa arranca—.
     */
    private function validateEntryType(Validator $validator): void
    {
        $type = (string) $this->input('entry_type');

        if ($type === Entry::SUPPLIER_TYPE && blank($this->input('supplier_id'))) {
            $validator->errors()->add('supplier_id', 'Una entrada por compra necesita el proveedor.');
        }

        if ($type !== Entry::INITIAL_TYPE) {
            return;
        }

        if (filled($this->input('supplier_id'))) {
            $validator->errors()->add('supplier_id', 'El inventario inicial no tiene proveedor.');
        }

        if (filled($this->input('sourceable_id'))) {
            $validator->errors()->add('sourceable_id', 'El inventario inicial no sale de ningún documento.');
        }
    }

    /**
     * El resultado del control de calidad tiene que decir lo mismo que las
     * líneas: aprobado no rechaza nada, rechazado no acepta nada y parcial es
     * el punto medio. Y todo lo que no sigue pendiente lo firma alguien.
     *
     * @param  array<int, array<string, mixed>>  $active
     */
    private function validateInspection(Validator $validator, array $active): void
    {
        $status = (string) $this->input('inspection_status');

        if ($status === 'pending') {
            return;
        }

        if (blank($this->input('inspected_by'))) {
            $validator->errors()->add('inspected_by', 'Indica quién hizo el control de calidad.');
        }

        $rejected = 0.0;
        $received = 0.0;

        foreach ($active as $line) {
            $quantity = round((float) ($line['quantity'] ?? 0), 4);
            $lineRejected = min(round((float) ($line['rejected_quantity'] ?? 0), 4), $quantity);

            $rejected += $lineRejected;
            $received += $quantity - $lineRejected;
        }

        if ($status === 'approved' && $rejected > 0) {
            $validator->errors()->add(
                'inspection_status',
                'Una entrada aprobada no puede tener cantidades rechazadas.',
            );
        }

        if ($status === 'rejected' && $received > 0) {
            $validator->errors()->add(
                'inspection_status',
                'Una entrada rechazada no puede aceptar cantidad alguna.',
            );
        }

        if ($status === 'partial' && ($rejected === 0.0 || $received === 0.0)) {
            $validator->errors()->add(
                'inspection_status',
                'Una entrada parcial acepta una parte y rechaza otra.',
            );
        }
    }
}
