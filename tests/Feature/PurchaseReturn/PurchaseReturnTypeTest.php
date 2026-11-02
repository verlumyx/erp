<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseOrder\Commands\ApplyPurchaseOrderReceiptCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Services\PurchaseOrderApplyReceiptService;

use function Pest\Laravel\actingAs;

function orderOriginInvoice(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    PurchaseOrder $order,
    float $quantity,
): PurchaseInvoice {
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_price' => 25,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    return $invoice;
}

function makeConfirmedOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    float $quantity = 10,
): PurchaseOrder {
    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_price' => 25,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    return $order->refresh()->load('lines');
}

test('it creates a definitive return by default and stores its type', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    expect($return->type)->toBe('definitive');
});

test('a replacement return fails if no invoice is provided', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'type' => 'replacement',
        'purchase_invoice_id' => null,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('type');
});

test('a replacement return fails if invoice does not originate from a purchase order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'type' => 'replacement',
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'purchase_invoice_line_id' => $invoice->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('type');
});

test('a replacement return succeeds when invoice originates from a purchase order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = makeConfirmedOrder($user, $company, $supplier, $warehouse, $item, $unit, 10);
    $invoice = orderOriginInvoice($user, $company, $supplier, $warehouse, $item, $unit, $order, 10);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'type' => 'replacement',
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'warehouse_id' => $warehouse->id,
            'purchase_invoice_line_id' => $invoice->lines->first()->id,
        ]],
    ]);

    expect($return->type)->toBe('replacement');
    expect($return->isReplacement())->toBeTrue();
});

test('confirming a replacement return reopens received quantity in the purchase order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = makeConfirmedOrder($user, $company, $supplier, $warehouse, $item, $unit, 10);
    $orderLine = $order->lines->first();

    // Mark 10 units received on the PO
    app(PurchaseOrderApplyReceiptService::class)->execute(new ApplyPurchaseOrderReceiptCommand(
        companyId: $company->id,
        purchaseOrderLineId: $orderLine->id,
        receivedDelta: 10,
    ));

    $invoice = orderOriginInvoice($user, $company, $supplier, $warehouse, $item, $unit, $order, 10);

    // Order is now 100% received and 100% invoiced => status is completed
    expect($order->refresh()->status)->toBe('completed');
    expect((float) $orderLine->refresh()->received_quantity)->toBe(10.0);
    expect((float) $orderLine->pending_quantity)->toBe(0.0);

    // Create a replacement return for 4 units
    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'type' => 'replacement',
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'warehouse_id' => $warehouse->id,
            'purchase_invoice_line_id' => $invoice->lines->first()->id,
        ]],
    ]);

    // Confirm the return
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    // Purchase order line received_quantity has decreased from 10 to 6
    expect((float) $orderLine->refresh()->received_quantity)->toBe(6.0);
    // Pending quantity has increased from 0 to 4
    expect((float) $orderLine->pending_quantity)->toBe(4.0);
    // Purchase order status has rolled back to partial!
    expect($order->refresh()->status)->toBe('partial');

    // A draft dispatch mirror was generated to take goods out
    $dispatch = Dispatch::where('sourceable_id', $return->id)->first();
    expect($dispatch)->not->toBeNull();
    expect($dispatch->status)->toBe('draft');
});

test('cancelling a confirmed replacement return restores received quantity in the purchase order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = makeConfirmedOrder($user, $company, $supplier, $warehouse, $item, $unit, 10);
    $orderLine = $order->lines->first();

    app(PurchaseOrderApplyReceiptService::class)->execute(new ApplyPurchaseOrderReceiptCommand(
        companyId: $company->id,
        purchaseOrderLineId: $orderLine->id,
        receivedDelta: 10,
    ));

    $invoice = orderOriginInvoice($user, $company, $supplier, $warehouse, $item, $unit, $order, 10);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'type' => 'replacement',
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'warehouse_id' => $warehouse->id,
            'purchase_invoice_line_id' => $invoice->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect((float) $orderLine->refresh()->received_quantity)->toBe(6.0);

    // Now cancel the replacement return
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    // Received quantity is restored to 10.0 and order is completed again
    expect((float) $orderLine->refresh()->received_quantity)->toBe(10.0);
    expect((float) $orderLine->pending_quantity)->toBe(0.0);
    expect($order->refresh()->status)->toBe('completed');
});

test('a replacement return is not creditable with a credit note', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = makeConfirmedOrder($user, $company, $supplier, $warehouse, $item, $unit, 10);
    $orderLine = $order->lines->first();

    app(PurchaseOrderApplyReceiptService::class)->execute(new ApplyPurchaseOrderReceiptCommand(
        companyId: $company->id,
        purchaseOrderLineId: $orderLine->id,
        receivedDelta: 10,
    ));

    $invoice = orderOriginInvoice($user, $company, $supplier, $warehouse, $item, $unit, $order, 10);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'type' => 'replacement',
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'warehouse_id' => $warehouse->id,
            'purchase_invoice_line_id' => $invoice->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    // It is filtered out of creditable returns
    $lookupResponse = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', ['company' => $company->id]));

    $lookupResponse->assertOk();
    expect($lookupResponse->json('data'))->toHaveCount(0);

    // Attempting to create a credit note pointing to this replacement return fails validation
    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'purchase_return_id' => $return->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors(['purchase_return_id' => 'Una devolución por reposición no se puede acreditar con nota de crédito: espera el reemplazo físico.']);
});
