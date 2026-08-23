<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a purchase credit note can be created', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'supplier_document_number' => 'NC-000123',
        'reason' => 'discount',
        'notes' => 'Rebaja acordada después de recibir la mercancía.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('purchase-credit-notes.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $note = PurchaseCreditNote::with('lines')->find($payload['id']);
    expect($note)->not->toBeNull();
    expect($note->code)->toBe('NCP000001');
    expect($note->status)->toBe('draft');
    expect($note->company_id)->toBe($company->id);
    expect($note->created_by)->toBe($user->id);
    expect($note->supplier_id)->toBe($supplier->id);
    expect($note->supplier_document_number)->toBe('NC-000123');
    expect($note->reason)->toBe('discount');
    /** Una nota que no saca mercancía es lo normal: solo baja la deuda. */
    expect($note->affects_inventory)->toBe('no');
    expect($note->lines)->toHaveCount(1);

    $line = $note->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(2.0);
    expect((float) $line->base_quantity)->toBe(2.0);
    expect((float) $line->subtotal)->toBe(50.0);
});

test('the totals are calculated on the backend and ignore what the client sends', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'subtotal' => 999999,
        'total' => 999999,
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
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $note = PurchaseCreditNote::with('lines')->find($payload['id']);
    $line = $note->lines->first();

    // 10 × 100 = 1000, −10 % = 900 de base; 16 % de impuesto = 144.
    expect((float) $line->discount_amount)->toBe(100.0);
    expect((float) $line->subtotal)->toBe(900.0);
    expect((float) $line->tax_amount)->toBe(144.0);
    expect((float) $line->total)->toBe(1044.0);
    /** La retención se practica sobre el impuesto, no sobre la base. */
    expect((float) $line->withholding_amount)->toBe(108.0);

    expect((float) $note->subtotal)->toBe(900.0);
    expect((float) $note->tax_amount)->toBe(144.0);
    expect((float) $note->total)->toBe(1044.0);
    /** El crédito nace entero: todavía no se ha aplicado a ninguna factura. */
    expect((float) $note->applied_amount)->toBe(0.0);
    expect((float) $note->balance)->toBe(1044.0);
});

test('the bolivar amounts are frozen in the header', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);

    // 50 USD a la tasa 36,5 del escenario.
    expect((float) $note->exchange_rate)->toBe(36.5);
    expect((float) $note->subtotal_ves)->toBe(1825.0);
    expect((float) $note->total_ves)->toBe(1825.0);
    expect((float) $note->tax_amount_ves)->toBe(0.0);
});

test('base_quantity converts the line to the base unit of the item', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $box->id, 'quantity' => 5, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = PurchaseCreditNote::with('lines')->find($payload['id'])->lines->first();
    expect((float) $line->quantity)->toBe(5.0);
    expect((float) $line->base_quantity)->toBe(60.0);
});

test('the code auto-increments per company', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $first = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);
    $second = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);

    expect($first->code)->toBe('NCP000001');
    expect($second->code)->toBe('NCP000002');
});

test('the note requires at least one line', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, ['lines' => []]),
        )
        ->assertSessionHasErrors('lines');
});

test('the supplier, the date and the reason are required', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'supplier_id' => '',
                'note_date' => '',
                'reason' => '',
            ]),
        )
        ->assertSessionHasErrors(['supplier_id', 'note_date', 'reason']);
});

test('a supplier from another company is rejected', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'supplier_id' => Supplier::factory()->create()->id,
            ]),
        )
        ->assertSessionHasErrors('supplier_id');
});

test('a reason outside the catalog is rejected', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, ['reason' => 'porque_si']),
        )
        ->assertSessionHasErrors('reason');
});

test('the reason detail is required when the reason is other', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, ['reason' => 'other']),
        )
        ->assertSessionHasErrors('reason_detail');

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'reason' => 'other',
        'reason_detail' => 'El proveedor reconoció un error de facturación del año pasado.',
    ]);

    expect($note->reason)->toBe('other');
    expect($note->reason_detail)->toBe('El proveedor reconoció un error de facturación del año pasado.');
});

test('a note that affects inventory needs a warehouse on every line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, ['affects_inventory' => 'yes']),
        )
        ->assertSessionHasErrors('lines.0.warehouse_id');

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'affects_inventory' => 'yes',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 25,
                'warehouse_id' => $warehouse->id,
            ],
        ],
    ]);

    expect($note->affects_inventory)->toBe('yes');
    expect($note->lines->first()->warehouse_id)->toBe($warehouse->id);
});

test('a warehouse from another company is rejected on the line', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'affects_inventory' => 'yes',
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 2,
                        'unit_price' => 25,
                        'warehouse_id' => Warehouse::factory()->create()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.warehouse_id');
});

test('the line unit must belong to the item', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $stray = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'lines' => [
                    ['item_id' => $item->id, 'measurement_unit_id' => $stray->id, 'quantity' => 1, 'unit_price' => 10],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('the tax chosen for a line is kept with its percentages', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
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
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = PurchaseCreditNote::with('lines')->find($payload['id'])->lines->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_percent)->toBe(16.0);
    expect((float) $line->tax_amount)->toBe(16.0);
    expect((float) $line->withholding_amount)->toBe(12.0);
});

test('a note can correct an invoice and trace its lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 3,
                'unit_price' => 25,
                'purchase_invoice_line_id' => $invoiceLine->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $note = PurchaseCreditNote::with('lines')->find($payload['id']);

    expect($note->purchase_invoice_id)->toBe($invoice->id);
    expect($note->lines->first()->purchase_invoice_line_id)->toBe($invoiceLine->id);
});

test('a note without an invoice cannot credit an invoice line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 25,
                        'purchase_invoice_line_id' => $invoice->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.purchase_invoice_line_id');
});

test('an invoice of another supplier cannot be corrected by the note', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);
    $invoice = createPurchaseInvoice($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_invoice_id');

    expect(PurchaseCreditNote::count())->toBe(0);
});

test('an invoice of another company cannot be corrected by the note', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $foreign = PurchaseInvoice::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_invoice_id' => $foreign->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_invoice_id');
});

test('a cancelled invoice cannot be credited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    PurchaseInvoice::where('id', $invoice->id)->update(['status' => 'cancelled']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_invoice_id');
});

test('a line of another invoice cannot be credited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $other = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 25,
                        'purchase_invoice_line_id' => $other->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.purchase_invoice_line_id');
});

test('the credited quantity cannot exceed the invoiced one', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    /** La factura del escenario trae una línea de 10 unidades. */
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $creditLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 25,
        'purchase_invoice_line_id' => $invoiceLine->id,
    ];

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
                'lines' => [$creditLine(11)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    expect(PurchaseCreditNote::count())->toBe(0);
});

test('what another note already credited lowers the remaining quantity', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $creditLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 25,
        'purchase_invoice_line_id' => $invoiceLine->id,
    ];

    createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$creditLine(7)],
    ]);

    /** De las 10 facturadas quedan 3: pedir 4 sobra. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
                'lines' => [$creditLine(4)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    $second = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$creditLine(3)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(3.0);
});

test('a cancelled note gives its credited quantity back', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $creditLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 25,
        'purchase_invoice_line_id' => $invoiceLine->id,
    ];

    $first = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$creditLine(10)],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $first->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $second = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$creditLine(10)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(10.0);
});

test('a user without permission cannot create a purchase credit note', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();
    assignRoleWithPermissions($user, $company, ['purchase-credit-notes.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit),
        )
        ->assertForbidden();
});

test('the form only offers the active taxes of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id]);
    Tax::factory()->inactive()->create(['company_id' => $company->id]);
    Tax::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-credit-notes/create')
                ->has('options.taxes', 1)
                /* Ni los artículos, ni los proveedores, ni las facturas viajan. */
                ->missing('options.items')
                ->missing('options.suppliers')
                ->missing('options.purchaseInvoices'),
        );
});

test('an inactive item cannot be credited', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $inactive = Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'inactive',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'lines' => [
                    ['item_id' => $inactive->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.item_id');
});
