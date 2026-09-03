<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

/**
 * Movimientos del kardex que escribió una factura de compra.
 *
 * Se busca sólo por el id del documento: la factura ya no es un origen válido
 * del kardex, así que su constante de tipo dejó de existir.
 */
function purchaseInvoiceMovements(PurchaseInvoice $invoice): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
        ->where('origin_id', $invoice->id)
        ->orderBy('created_at')
        ->get();
}

function movePurchaseInvoiceTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    PurchaseInvoice $invoice,
    string $status,
    array $payload = [],
): \Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => $status, ...$payload],
        );
}

test('confirming loads the payable of the supplier net of the withholding', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseReturnScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
            'tax_percent' => 16,
            'withholding_percent' => 75,
        ]],
    ]);

    /** 250 + 40 de IVA = 290; la retención de 30 se entera al fisco, no al proveedor. */
    expect((float) $invoice->total)->toBe(290.0);
    expect((float) $invoice->withholding_amount)->toBe(30.0);

    /** En borrador no debe nada todavía. */
    expect((float) $supplier->refresh()->current_balance)->toBe(0.0);

    movePurchaseInvoiceTo($user, $company, $invoice, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $supplier->refresh()->current_balance)->toBe(260.0);
});

test('confirming moves no stock and leaves the average cost of the item untouched', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    /** 250 de mercancía entre 10 unidades base: la línea declara 25. */
    expect((float) $invoice->lines->first()->landed_cost)->toBe(25.0);

    movePurchaseInvoiceTo($user, $company, $invoice, 'confirmed')->assertSessionHasNoErrors();

    /**
     * Sólo el Ajuste, la Entrada y el Despacho escriben en el kardex: la
     * factura declara el costo, pero la mercancía entra con su Entrada.
     */
    expect(purchaseInvoiceMovements($invoice))->toHaveCount(0);
    expect(InventoryMovement::query()->where('company_id', $company->id)->count())->toBe(0);

    /** El landed cost no revalúa nada: el costo promedio lo recalcula la Entrada. */
    expect((float) $item->refresh()->average_cost)->toBe(0.0);
});

test('confirming advances the purchase order that originated the invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    $orderLine = $order->lines->first();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
            'quantity' => 4,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseInvoiceTo($user, $company, $invoice, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $orderLine->refresh()->invoiced_quantity)->toBe(4.0);
    expect((float) $order->refresh()->invoiced_percent)->toBe(40.0);
});

test('cancelling a confirmed invoice reverses the balance and the order', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseReturnScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    $orderLine = $order->lines->first();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseInvoiceTo($user, $company, $invoice, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $supplier->refresh()->current_balance)->toBe(250.0);
    expect((float) $orderLine->refresh()->invoiced_quantity)->toBe(10.0);

    movePurchaseInvoiceTo($user, $company, $invoice, 'cancelled', [
        'cancellation_reason' => 'El proveedor la emitió con el RIF equivocado.',
    ])->assertSessionHasNoErrors();

    expect($invoice->refresh()->status)->toBe('cancelled');
    expect((float) $supplier->refresh()->current_balance)->toBe(0.0);
    expect((float) $orderLine->refresh()->invoiced_quantity)->toBe(0.0);

    /** Nada que revertir en el kardex: la factura nunca lo tocó. */
    expect(purchaseInvoiceMovements($invoice))->toHaveCount(0);
});

test('cancelling a draft moves no balance at all', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseReturnScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    movePurchaseInvoiceTo($user, $company, $invoice, 'cancelled', [
        'cancellation_reason' => 'Se capturó dos veces.',
    ])->assertSessionHasNoErrors();

    expect((float) $supplier->refresh()->current_balance)->toBe(0.0);
    expect(purchaseInvoiceMovements($invoice))->toHaveCount(0);
});
