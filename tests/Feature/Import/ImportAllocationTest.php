<?php

declare(strict_types=1);

use App\Modules\ItemLot\Models\ItemLot;

it('reparte por lote y capitaliza solo lo que sigue en la bodega', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'location_id' => $location->id,
            'lots' => [
                ['lot_number' => 'L-A', 'quantity' => 4],
                ['lot_number' => 'L-B', 'quantity' => 6],
            ],
        ]],
    ]);

    /** El lote B se vendió entero antes de que llegara la factura del flete. */
    $sold = ItemLot::where('item_id', $item->id)->where('lot_number', 'L-B')->firstOrFail();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'type' => 'out',
        'quantity' => 6,
        'lotId' => $sold->id,
    ]);

    $import = createImport($user, $company, $warehouse, $entry);

    $line = $import->lines->first();
    $lots = $line->lots->sortBy('line_number')->values();

    expect($lots)->toHaveCount(2)
        /** 100 de flete sobre 250 de valor: 0,4 por cada dólar que entró. */
        ->and((float) $lots[0]->allocated_amount)->toBe(40.0)
        ->and((float) $lots[1]->allocated_amount)->toBe(60.0)
        ->and((float) $lots[0]->remaining_quantity)->toBe(4.0)
        ->and((float) $lots[1]->remaining_quantity)->toBe(0.0)
        /** Lo del lote vendido no llega al inventario: es gasto del período. */
        ->and((float) $lots[0]->capitalized_amount)->toBe(40.0)
        ->and((float) $lots[1]->capitalized_amount)->toBe(0.0)
        ->and((float) $lots[1]->variance_amount)->toBe(60.0);

    expect((float) $line->allocated_amount)->toBe(100.0)
        ->and((float) $line->capitalized_amount)->toBe(40.0)
        ->and((float) $line->variance_amount)->toBe(60.0)
        ->and((float) $import->capitalized_amount)->toBe(40.0)
        ->and((float) $import->variance_amount)->toBe(60.0);
});

it('lleva al ajuste una fila de lote por cada caja con saldo vivo', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'location_id' => $location->id,
            'lots' => [
                ['lot_number' => 'L-A', 'quantity' => 4],
                ['lot_number' => 'L-B', 'quantity' => 6],
            ],
        ]],
    ]);

    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();

    $line = importAdjustment($import)->lines->first();

    expect($line->lots)->toHaveCount(2)
        /** Cada caja se revalúa a su propio promedio más su incremento. */
        ->and($line->lots->pluck('unit_cost')->map(fn ($cost): float => (float) $cost)->all())
        ->toBe([35.0, 35.0])
        ->and($line->lots->pluck('counted_quantity')->map(fn ($q): float => (float) $q)->all())
        ->toBe([4.0, 6.0]);
});

it('reparte por cantidad cuando así se pide', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = importScenario();

    $expensive = \App\Modules\Item\Models\Item::factory()->create([
        'company_id' => $company->id,
        'average_cost' => 75,
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $expensive->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'location_id' => $location->id],
            ['item_id' => $expensive->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'location_id' => $location->id],
        ],
    ]);

    $import = createImport($user, $company, $warehouse, $entry, ['allocation_method' => 'quantity']);

    /** Mismas unidades, mismo gasto: el precio de cada una no cuenta. */
    expect($import->lines->pluck('allocated_amount')->map(fn ($a): float => (float) $a)->all())
        ->toBe([50.0, 50.0]);
});

it('reparte por peso cuando el artículo lo trae', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = importScenario();

    $item->update(['weight' => 3]);

    $light = \App\Modules\Item\Models\Item::factory()->create([
        'company_id' => $company->id,
        'average_cost' => 25,
        'weight' => 1,
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $light->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'location_id' => $location->id],
            ['item_id' => $light->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'location_id' => $location->id],
        ],
    ]);

    $import = createImport($user, $company, $warehouse, $entry, ['allocation_method' => 'weight']);

    /** 30 kilos contra 10: tres cuartas partes del flete son del pesado. */
    expect($import->lines->pluck('allocated_amount')->map(fn ($a): float => (float) $a)->all())
        ->toBe([75.0, 25.0]);
});

it('deja fuera del reparto la línea que se saca a mano', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = importScenario();

    $other = \App\Modules\Item\Models\Item::factory()->create([
        'company_id' => $company->id,
        'average_cost' => 25,
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $other->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'location_id' => $location->id],
            ['item_id' => $other->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'location_id' => $location->id],
        ],
    ]);

    $import = createImport($user, $company, $warehouse, $entry);

    $excluded = $import->lines->firstWhere('item_id', $other->id);

    $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('imports.update', ['company' => $company->id, 'id' => $import->id]),
            importPayload($warehouse, $entry, [
                'id' => $import->id,
                'lines' => [
                    ['entry_line_id' => $excluded->entry_line_id, 'status' => 'inactive'],
                ],
            ]),
        )
        ->assertSessionHasNoErrors();

    $import->refresh()->load('lines');

    expect((float) $import->lines->firstWhere('item_id', $other->id)->allocated_amount)->toBe(0.0)
        ->and((float) $import->lines->firstWhere('item_id', $item->id)->allocated_amount)->toBe(100.0)
        ->and((float) $import->total_base_value)->toBe(250.0);
});
