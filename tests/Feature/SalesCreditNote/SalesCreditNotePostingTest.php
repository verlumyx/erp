<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\Item\Models\Item;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\Warehouse\Models\Warehouse;

/** Cualquier asiento de kardex que apunte a la nota, viva o contrapartida. */
function movementsOf(SalesCreditNote $note): \Illuminate\Support\Collection
{
    return InventoryMovement::query()
        ->where('origin_id', $note->id)
        ->orderBy('created_at')
        ->get();
}

test('a sales credit note never writes in the kardex', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    /** La bodega no importa: la línea la lleva como dato, no como destino. */
    $loose = createSalesCreditNote($user, $company, $client, $item, $unit);

    expect($loose->lines->first()->warehouse_id)->toBeNull();

    moveSalesCreditNoteTo($user, $company, $loose, 'confirmed')->assertSessionHasNoErrors();

    expect($loose->refresh()->status)->toBe('confirmed');
    expect(movementsOf($loose))->toHaveCount(0);

    /** Ni siquiera una bodega sin ubicación por defecto estorba: nadie la usa. */
    $bare = Warehouse::factory()->create(['company_id' => $company->id]);

    $stocked = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 3,
            'unit_price' => 25,
            'warehouse_id' => $bare->id,
        ]],
    ]);

    foreach (['confirmed', 'cancelled'] as $status) {
        moveSalesCreditNoteTo($user, $company, $stocked, $status)->assertSessionHasNoErrors();
    }

    expect($stocked->refresh()->status)->toBe('cancelled');
    /** Ni al confirmar ni al anular: la mercancía reingresa con su Entrada. */
    expect(movementsOf($stocked))->toHaveCount(0);
    expect(InventoryMovement::query()->where('company_id', $company->id)->count())->toBe(0);
});

test('a loose line freezes the cost the item carries today', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    Item::where('id', $item->id)->update([
        'cost_method' => 'average',
        'average_cost' => 12.5,
    ]);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 25,
        ]],
    ]);

    /** Sin factura detrás, la línea se valora al costo vigente del artículo. */
    expect((float) $note->lines->first()->unit_cost)->toBe(12.5);
});

test('a line tied to an invoice line keeps the cost that line froze', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

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
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 25,
            'sales_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    expect((float) $note->lines->first()->unit_cost)->toBe(12.5);
});
