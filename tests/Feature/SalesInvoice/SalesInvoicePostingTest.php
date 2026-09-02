<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\SalesInvoice\Models\SalesInvoice;

use function Pest\Laravel\actingAs;

/** Movimientos del kardex que escribió una factura de venta. */
function salesInvoiceMovements(SalesInvoice $invoice): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
        ->where('origin_type', SalesInvoice::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $invoice->id)
        ->orderBy('created_at')
        ->get();
}

test('a direct invoice takes the goods out of the warehouse', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    /** La bodega tiene existencia comprada a 20 y a 40: promedio 30. */
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 40]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'affects_inventory' => 'yes',
    ]);

    /** En borrador nada se ha movido. */
    expect(salesInvoiceMovements($invoice))->toHaveCount(0);

    confirmSalesInvoice($user, $company, $invoice);

    $movements = salesInvoiceMovements($invoice);
    expect($movements)->toHaveCount(1);

    $movement = $movements->first();
    expect($movement->type)->toBe('out');
    expect($movement->warehouse_id)->toBe($warehouse->id);
    expect($movement->location_id)->toBe($location->id);
    expect((float) $movement->quantity)->toBe(2.0);
    /** La salida se valora al promedio vigente. */
    expect((float) $movement->unit_cost)->toBe(30.0);
    expect((float) $movement->balance_quantity)->toBe(18.0);
});

test('an invoice whose goods left with a dispatch moves no stock', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'affects_inventory' => 'no',
    ]);

    confirmSalesInvoice($user, $company, $invoice);

    expect(salesInvoiceMovements($invoice))->toHaveCount(0);
});

test('the exit is written in the base unit of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 10]);

    $box = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $company->id]);
    \App\Modules\Item\Models\ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'conversion_factor' => 12,
        'is_base' => 'no',
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'affects_inventory' => 'yes',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
            'unit_price' => 240,
        ]],
    ]);

    confirmSalesInvoice($user, $company, $invoice);

    $movement = salesInvoiceMovements($invoice)->first();
    expect((float) $movement->quantity)->toBe(24.0);
    expect((float) $movement->balance_quantity)->toBe(76.0);
});

test('an invoice the warehouse cannot cover is not issued', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 1, 'unitCost' => 20]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'affects_inventory' => 'yes',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('draft');
    expect(salesInvoiceMovements($invoice))->toHaveCount(0);
});

test('cancelling an issued invoice puts the goods back', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'affects_inventory' => 'yes',
    ]);

    confirmSalesInvoice($user, $company, $invoice);

    expect((float) salesInvoiceMovements($invoice)->first()->balance_quantity)->toBe(8.0);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Se facturó al cliente equivocado.'],
        )
        ->assertSessionHasNoErrors();

    /** La contrapartida no borra nada: queda el asiento y su reverso. */
    $movements = salesInvoiceMovements($invoice);
    expect($movements)->toHaveCount(2);
    expect($movements->last()->type)->toBe('in');
    expect((float) $movements->last()->balance_quantity)->toBe(10.0);
});

test('an invoice without inventory freezes the cost of the dispatch that took the goods out', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    /** La bodega tiene existencia comprada a 20 y a 40: promedio 30. */
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 40]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 100,
            'location_id' => $location->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    /** Después de despachar, el promedio de lo que queda sigue siendo 30. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'affects_inventory' => 'no',
        'dispatch_id' => $dispatch->id,
    ]);

    confirmSalesInvoice($user, $company, $invoice);

    $line = $invoice->refresh()->load('lines')->lines->first();

    /** El costo lo fija el movimiento del despacho, no el artículo. */
    expect((float) $line->unit_cost)->toBe(30.0);
    expect((float) $line->total_cost)->toBe(60.0);
    expect((float) $line->margin_amount)->toBe(140.0);
    expect((float) $invoice->total_cost)->toBe(60.0);
    expect(salesInvoiceMovements($invoice))->toHaveCount(0);
});
