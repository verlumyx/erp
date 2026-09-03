<?php

declare(strict_types=1);

use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a purchase invoice can be created', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'supplier_invoice_number' => '00-123456',
        'supplier_invoice_series' => 'A',
        'received_date' => now()->addDay()->toDateString(),
        'notes' => 'Llegó con la guía de despacho.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('purchase-invoices.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $invoice = PurchaseInvoice::with('lines')->find($payload['id']);
    expect($invoice)->not->toBeNull();
    expect($invoice->code)->toBe('FCO000001');
    expect($invoice->status)->toBe('draft');
    expect($invoice->payment_status)->toBe('pending');
    expect($invoice->company_id)->toBe($company->id);
    expect($invoice->created_by)->toBe($user->id);
    expect($invoice->supplier_id)->toBe($supplier->id);
    expect($invoice->supplier_invoice_number)->toBe('00-123456');
    expect($invoice->supplier_invoice_series)->toBe('A');
    expect($invoice->affects_inventory)->toBe('yes');
    expect($invoice->lines)->toHaveCount(1);

    $line = $invoice->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(10.0);
    expect((float) $line->base_quantity)->toBe(10.0);
    expect((float) $line->subtotal)->toBe(250.0);
    expect((float) $line->returned_quantity)->toBe(0.0);
});

test('the due date is derived from the credit days of the supplier', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'payment_term_days' => 30,
    ]);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(PurchaseInvoice::find($payload['id'])->due_date->format('Y-m-d'))
        ->toBe(now()->addDays(30)->toDateString());
});

test('an explicit due date wins over the credit days of the supplier', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'payment_term_days' => 30,
    ]);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'due_date' => now()->addDays(7)->toDateString(),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(PurchaseInvoice::find($payload['id'])->due_date->format('Y-m-d'))
        ->toBe(now()->addDays(7)->toDateString());
});

test('the totals are calculated on the backend and ignore what the client sends', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'subtotal' => 999999,
        'total' => 999999,
        'discount_amount' => 50,
        'freight_amount' => 30,
        'other_charges' => 20,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
                'discount_percent' => 10,
                'tax_percent' => 16,
                'withholding_percent' => 75,
                'subtotal' => 1,
                'total' => 1,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = PurchaseInvoice::with('lines')->find($payload['id']);
    $line = $invoice->lines->first();

    // 10 × 100 = 1000, −10 % = 900 de base; 16 % de impuesto = 144.
    expect((float) $line->discount_amount)->toBe(100.0);
    expect((float) $line->subtotal)->toBe(900.0);
    expect((float) $line->tax_amount)->toBe(144.0);
    expect((float) $line->total)->toBe(1044.0);
    /** La retención se practica sobre el impuesto, no sobre la base. */
    expect((float) $line->withholding_amount)->toBe(108.0);

    // Cabecera: 900 − 50 de descuento + 144 de impuesto + 30 de flete + 20 de gastos.
    expect((float) $invoice->subtotal)->toBe(900.0);
    expect((float) $invoice->discount_amount)->toBe(50.0);
    expect((float) $invoice->tax_amount)->toBe(144.0);
    expect((float) $invoice->withholding_amount)->toBe(108.0);
    expect((float) $invoice->freight_amount)->toBe(30.0);
    expect((float) $invoice->other_charges)->toBe(20.0);
    expect((float) $invoice->total)->toBe(1044.0);
    /** La deuda nace entera: nadie ha pagado todavía. */
    expect((float) $invoice->paid_amount)->toBe(0.0);
    expect((float) $invoice->balance)->toBe(1044.0);
});

test('the freight and the other charges are prorated into the landed cost', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $second = \App\Modules\Item\Models\Item::factory()->create([
        'company_id' => $company->id,
        'is_purchasable' => 'yes',
    ]);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $second->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'freight_amount' => 100,
        'other_charges' => 50,
        'lines' => [
            // 300 de base: 2/3 del subtotal → 100 de los 150 de cargos.
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 30],
            // 150 de base: 1/3 del subtotal → 50 de los 150 de cargos.
            ['item_id' => $second->id, 'measurement_unit_id' => $unit->id, 'quantity' => 5, 'unit_price' => 30],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $lines = PurchaseInvoice::with('lines')->find($payload['id'])->lines->sortBy('line_number')->values();

    // (300 + 100) / 10 = 40 por unidad base.
    expect((float) $lines[0]->landed_cost)->toBe(40.0);
    // (150 + 50) / 5 = 40 por unidad base.
    expect((float) $lines[1]->landed_cost)->toBe(40.0);
});

test('without charges the landed cost is the cost of the line itself', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    // 10 × 25 = 250 entre 10 unidades base.
    expect((float) $invoice->lines->first()->landed_cost)->toBe(25.0);
});

test('the bolivar amounts are frozen in the header', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    // 250 USD a la tasa 36,5 del escenario.
    expect((float) $invoice->exchange_rate)->toBe(36.5);
    expect((float) $invoice->subtotal_ves)->toBe(9125.0);
    expect((float) $invoice->total_ves)->toBe(9125.0);
    expect((float) $invoice->tax_amount_ves)->toBe(0.0);
});

test('base_quantity converts the line to the base unit of the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $box->id, 'quantity' => 5, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = PurchaseInvoice::with('lines')->find($payload['id'])->lines->first();
    expect((float) $line->quantity)->toBe(5.0);
    expect((float) $line->base_quantity)->toBe(60.0);
});

test('the code auto-increments per company', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $first = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $second = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    expect($first->code)->toBe('FCO000001');
    expect($second->code)->toBe('FCO000002');
});

test('the same supplier cannot have two invoices with the same printed number', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'supplier_invoice_number' => '00-777777',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'supplier_invoice_number' => '00-777777',
            ]),
        )
        ->assertSessionHasErrors('supplier_invoice_number');
});

test('two suppliers can print the same invoice number', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'supplier_invoice_number' => '00-777777',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($other, $warehouse, $item, $unit, [
                'supplier_invoice_number' => '00-777777',
            ]),
        )
        ->assertSessionHasNoErrors();
});

test('the invoice requires at least one line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, ['lines' => []]),
        )
        ->assertSessionHasErrors('lines');
});

test('the supplier, the warehouse and the printed number are required', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'supplier_id' => '',
                'warehouse_id' => '',
                'supplier_invoice_number' => '',
            ]),
        )
        ->assertSessionHasErrors(['supplier_id', 'warehouse_id', 'supplier_invoice_number']);
});

test('a supplier from another company is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'supplier_id' => Supplier::factory()->create()->id,
            ]),
        )
        ->assertSessionHasErrors('supplier_id');
});

test('a warehouse from another company is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'warehouse_id' => Warehouse::factory()->create()->id,
            ]),
        )
        ->assertSessionHasErrors('warehouse_id');
});

test('the received date cannot be earlier than the issue date', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'received_date' => now()->subDay()->toDateString(),
            ]),
        )
        ->assertSessionHasErrors('received_date');
});

test('the due date cannot be earlier than the issue date', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'due_date' => now()->subDay()->toDateString(),
            ]),
        )
        ->assertSessionHasErrors('due_date');
});

test('the global discount cannot exceed the subtotal of the lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, ['discount_amount' => 1000]),
        )
        ->assertSessionHasErrors('discount_amount');
});

test('the line unit must belong to the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $stray = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'lines' => [
                    ['item_id' => $item->id, 'measurement_unit_id' => $stray->id, 'quantity' => 1, 'unit_price' => 10],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('the tax chosen for a line is kept with its percentages', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 100,
                'tax_id' => $tax->id,
                'tax_percent' => 16,
                'withholding_percent' => 75,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = PurchaseInvoice::with('lines')->find($payload['id'])->lines->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_percent)->toBe(16.0);
    expect((float) $line->tax_amount)->toBe(16.0);
    expect((float) $line->withholding_amount)->toBe(12.0);
});

test('an invoice can be born from a purchase order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 25,
                'sourceable_type' => \App\Modules\PurchaseOrder\Models\PurchaseOrderLine::MORPH_ALIAS,
                'sourceable_id' => $order->lines->first()->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = PurchaseInvoice::with('lines')->find($payload['id']);

    /** El alias del morph map, no el nombre de la clase. */
    expect($invoice->sourceable_type)->toBe('purchase_order');
    expect($invoice->sourceable_id)->toBe($order->id);
    expect($invoice->sourceable)->toBeInstanceOf(PurchaseOrder::class);
    expect($invoice->lines->first()->sourceable_type)->toBe('purchase_order_line');
    expect($invoice->lines->first()->sourceable_id)->toBe($order->lines->first()->id);

    /** La orden expone sus facturas por el otro extremo de la relación. */
    expect($order->fresh()->purchaseInvoices)->toHaveCount(1);
});

test('a direct invoice leaves the source document empty', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    expect($invoice->sourceable_type)->toBeNull();
    expect($invoice->sourceable_id)->toBeNull();
});

test('an order without its type is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'sourceable_id' => $order->id,
            ]),
        )
        ->assertSessionHasErrors('sourceable_type');
});

test('a source type outside the morph map is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'sourceable_type' => 'sales_order',
                'sourceable_id' => $order->id,
            ]),
        )
        ->assertSessionHasErrors('sourceable_type');
});

test('an order of another supplier cannot be the source of the invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);
    $order = sourcePurchaseOrder($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
                'sourceable_id' => $order->id,
            ]),
        )
        ->assertSessionHasErrors('sourceable_id');

    expect(PurchaseInvoice::count())->toBe(0);
});

test('an order of another company cannot be the source of the invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $foreign = PurchaseOrder::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
                'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
                'sourceable_id' => $foreign->id,
            ]),
        )
        ->assertSessionHasErrors('sourceable_id');
});

test('a user without permission cannot create a purchase invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();
    assignRoleWithPermissions($user, $company, ['purchase-invoices.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-invoices.store', ['company' => $company->id]),
            purchaseInvoicePayload($supplier, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});

test('the form only offers the active taxes of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id]);
    Tax::factory()->inactive()->create(['company_id' => $company->id]);
    Tax::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-invoices/create')
                ->has('options.taxes', 1),
        );
});

test('a line cannot invoice more than the order has left', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    /** Seis ya facturadas de las diez pedidas dejan cuatro por facturar. */
    $orderLine->update(['invoiced_quantity' => 6]);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 5,
                'unit_price' => 25,
                'sourceable_type' => \App\Modules\PurchaseOrder\Models\PurchaseOrderLine::MORPH_ALIAS,
                'sourceable_id' => $orderLine->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.quantity');
});

test('a line can invoice exactly what the order has left', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $orderLine->update(['invoiced_quantity' => 6]);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 25,
                'sourceable_type' => \App\Modules\PurchaseOrder\Models\PurchaseOrderLine::MORPH_ALIAS,
                'sourceable_id' => $orderLine->id,
            ],
        ],
    ]);

    expect((float) $invoice->lines->first()->quantity)->toBe(4.0);
});

test('a line cannot come from an order line of another order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $other = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 25,
                'sourceable_type' => \App\Modules\PurchaseOrder\Models\PurchaseOrderLine::MORPH_ALIAS,
                'sourceable_id' => $other->lines->first()->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.sourceable_id');
});

test('an invoice cannot be born from an order of another supplier', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $other = \App\Modules\Supplier\Models\Supplier::factory()->create(['company_id' => $company->id]);

    $order = sourcePurchaseOrder($user, $company, $other, $warehouse, $item, $unit);

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_id');
});
