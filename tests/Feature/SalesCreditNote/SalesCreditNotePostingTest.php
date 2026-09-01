<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\Item\Models\Item;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

/**
 * El escenario de una nota que reingresa mercancía: la bodega necesita
 * ubicación por defecto, porque el kardex no mueve saldo sin sitio.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Client\Models\Client,
 *     3: \App\Modules\Warehouse\Models\Warehouse,
 *     4: \App\Modules\Item\Models\Item,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit
 * }
 */
function salesCreditNotePostingScenario(): array
{
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    /** El kardex solo ve ubicaciones de bodegas que las usan. */
    Warehouse::where('id', $warehouse->id)->update(['uses_locations' => 'yes']);

    WarehouseLocation::factory()->default()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    return [$user, $company, $client, $warehouse, $item, $unit];
}

/** Las líneas de una nota que devuelve mercancía a la bodega dada. */
function returningLines(Item $item, $unit, Warehouse $warehouse, float $quantity = 3): array
{
    return [[
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 25,
        'warehouse_id' => $warehouse->id,
    ]];
}

/** Los asientos vivos que escribió la nota, sin las contrapartidas. */
function movementsOf(SalesCreditNote $note): \Illuminate\Support\Collection
{
    return InventoryMovement::query()
        ->where('origin_type', SalesCreditNote::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $note->id)
        ->orderBy('created_at')
        ->get();
}

test('a note that does not affect inventory writes nothing in the kardex', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNotePostingScenario();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect(movementsOf($note))->toHaveCount(0);
});

test('confirming a note that affects inventory brings the goods back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNotePostingScenario();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'affects_inventory' => 'yes',
        'lines' => returningLines($item, $unit, $warehouse),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $movements = movementsOf($note);

    expect($movements)->toHaveCount(1);
    expect($movements->first()->type)->toBe('in');
    expect($movements->first()->warehouse_id)->toBe($warehouse->id);
    /** En unidad base, que aquí coincide con la de la línea. */
    expect((float) $movements->first()->quantity)->toBe(3.0);
    expect($movements->first()->origin_line_id)->toBe($note->lines->first()->id);
});

test('the goods come back at the original cost of the sale', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNotePostingScenario();

    /** El costo congelado de la venta: el artículo lo lleva en su promedio. */
    Item::where('id', $item->id)->update([
        'cost_method' => 'average',
        'average_cost' => 12.5,
    ]);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'affects_inventory' => 'yes',
        'lines' => returningLines($item, $unit, $warehouse, 2),
    ]);

    expect((float) $note->lines->first()->unit_cost)->toBe(12.5);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect((float) movementsOf($note)->first()->unit_cost)->toBe(12.5);
});

test('a line tied to an invoice line reenters at the cost that line froze', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNotePostingScenario();

    Item::where('id', $item->id)->update([
        'cost_method' => 'average',
        'average_cost' => 40,
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    /** Lo que costó cuando se vendió, no lo que cuesta hoy. */
    $invoiceLine->update(['unit_cost' => 12.5]);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'affects_inventory' => 'yes',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 25,
            'warehouse_id' => $warehouse->id,
            'sales_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    expect((float) $note->lines->first()->unit_cost)->toBe(12.5);
});

test('cancelling a confirmed note writes the counterpart, it does not delete', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNotePostingScenario();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'affects_inventory' => 'yes',
        'lines' => returningLines($item, $unit, $warehouse),
    ]);

    foreach (['confirmed', 'cancelled'] as $status) {
        actingAs($user)->withSession(['current_company_id' => $company->id])
            ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
                'status' => $status,
            ])
            ->assertSessionHasNoErrors();
    }

    $movements = movementsOf($note);

    expect($movements)->toHaveCount(2);
    expect($movements->firstWhere('reversal_of_id', '!=', null)->type)->toBe('out');
    expect((float) $movements->firstWhere('reversal_of_id', '!=', null)->quantity)->toBe(3.0);
});

test('a service item does not reach the kardex', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesCreditNotePostingScenario();

    $service = Item::factory()->service()->create(['company_id' => $company->id]);
    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $note = createSalesCreditNote($user, $company, $client, $service, $unit, [
        'affects_inventory' => 'yes',
        'lines' => returningLines($service, $unit, $warehouse),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect(movementsOf($note))->toHaveCount(0);
    expect($note->refresh()->status)->toBe('confirmed');
});

test('a warehouse without a default location blocks the confirmation', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNotePostingScenario();

    $bare = Warehouse::factory()->create(['company_id' => $company->id]);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'affects_inventory' => 'yes',
        'lines' => returningLines($item, $unit, $bare),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('draft');
    expect(movementsOf($note))->toHaveCount(0);
});
