<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\SalesInvoice\Models\SalesInvoice;

use function Pest\Laravel\actingAs;

/**
 * Movimientos del kardex cuyo origen es esta factura de venta.
 *
 * No se filtra por `origin_type` porque la factura ya no es un origen válido
 * del kardex: solo el ajuste, la entrada y el despacho lo son.
 */
function salesInvoiceMovements(SalesInvoice $invoice): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
        ->where('origin_id', $invoice->id)
        ->orderBy('created_at')
        ->get();
}

test('issuing an invoice writes nothing in the kardex, and cancelling it writes nothing either', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $stockBefore = InventoryMovement::query()->count();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    confirmSalesInvoice($user, $company, $invoice);

    /** La mercancía sale con su despacho: la factura no mueve existencia. */
    expect(salesInvoiceMovements($invoice))->toHaveCount(0);
    expect(InventoryMovement::query()->count())->toBe($stockBefore);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Se facturó al cliente equivocado.'],
        )
        ->assertSessionHasNoErrors();

    /** Y si no asentó nada, al anularse no hay contrapartida que escribir. */
    expect($invoice->refresh()->status)->toBe('cancelled');
    expect(salesInvoiceMovements($invoice))->toHaveCount(0);
    expect(InventoryMovement::query()->count())->toBe($stockBefore);
});

test('an invoice can be issued for goods the warehouse no longer holds', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    /** La bodega está vacía: la mercancía ya salió con su despacho. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    confirmSalesInvoice($user, $company, $invoice);

    expect($invoice->refresh()->status)->toBe('confirmed');
    expect(salesInvoiceMovements($invoice))->toHaveCount(0);
});

test('the frozen cost is written in the base unit of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    \App\Modules\Item\Models\Item::where('id', $item->id)->update(['average_cost' => 10]);

    $box = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $company->id]);
    \App\Modules\Item\Models\ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'conversion_factor' => 12,
        'is_base' => 'no',
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
            'unit_price' => 240,
        ]],
    ]);

    confirmSalesInvoice($user, $company, $invoice);

    $line = $invoice->refresh()->load('lines')->lines->first();

    /** El costo va por unidad base: 2 cajas de 12 son 24 unidades a 10. */
    expect((float) $line->unit_cost)->toBe(10.0);
    expect((float) $line->total_cost)->toBe(240.0);
    expect((float) $invoice->total_cost)->toBe(240.0);
});

test('an invoice freezes the cost of the dispatch that took the goods out', function () {
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
