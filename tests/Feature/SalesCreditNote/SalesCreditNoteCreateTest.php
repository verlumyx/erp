<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a sales credit note can be created', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $payload = salesCreditNotePayload($client, $item, $unit, [
        'note_series' => 'A',
        'reason' => 'discount',
        'notes' => 'Rebaja acordada después de entregar la mercancía.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-credit-notes.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('sales-credit-notes.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $note = SalesCreditNote::with('lines')->find($payload['id']);
    expect($note)->not->toBeNull();
    expect($note->code)->toBe('NCC000001');
    expect($note->status)->toBe('draft');
    expect($note->company_id)->toBe($company->id);
    expect($note->created_by)->toBe($user->id);
    expect($note->client_id)->toBe($client->id);
    expect($note->note_series)->toBe('A');
    /** El correlativo fiscal se quema al confirmar, nunca en borrador. */
    expect($note->note_number)->toBeNull();
    expect($note->reason)->toBe('discount');
    /** Una nota que no reingresa mercancía es lo normal: solo baja la deuda. */
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
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $payload = salesCreditNotePayload($client, $item, $unit, [
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
        ->post(route('sales-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $note = SalesCreditNote::with('lines')->find($payload['id']);
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
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit);

    // 50 USD a la tasa 36,5 del escenario.
    expect((float) $note->exchange_rate)->toBe(36.5);
    expect((float) $note->subtotal_ves)->toBe(1825.0);
    expect((float) $note->total_ves)->toBe(1825.0);
    expect((float) $note->tax_amount_ves)->toBe(0.0);
});

test('base_quantity converts the line to the base unit of the item', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = salesCreditNotePayload($client, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $box->id, 'quantity' => 5, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesCreditNote::with('lines')->find($payload['id'])->lines->first();
    expect((float) $line->quantity)->toBe(5.0);
    expect((float) $line->base_quantity)->toBe(60.0);
});

test('the code auto-increments per company', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $first = createSalesCreditNote($user, $company, $client, $item, $unit);
    $second = createSalesCreditNote($user, $company, $client, $item, $unit);

    expect($first->code)->toBe('NCC000001');
    expect($second->code)->toBe('NCC000002');
});

test('the note requires at least one line', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, ['lines' => []]),
        )
        ->assertSessionHasErrors('lines');
});

test('the client, the date and the reason are required', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'client_id' => '',
                'note_date' => '',
                'reason' => '',
            ]),
        )
        ->assertSessionHasErrors(['client_id', 'note_date', 'reason']);
});

test('a client from another company is rejected', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'client_id' => Client::factory()->create()->id,
            ]),
        )
        ->assertSessionHasErrors('client_id');
});

test('a reason outside the catalog is rejected', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, ['reason' => 'porque_si']),
        )
        ->assertSessionHasErrors('reason');
});

test('cancellation is a valid reason for a sales credit note', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'reason' => 'cancellation',
    ]);

    expect($note->reason)->toBe('cancellation');
});

test('the reason detail is required when the reason is other', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, ['reason' => 'other']),
        )
        ->assertSessionHasErrors('reason_detail');

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'reason' => 'other',
        'reason_detail' => 'El cliente reclamó un error de facturación del año pasado.',
    ]);

    expect($note->reason)->toBe('other');
    expect($note->reason_detail)->toBe('El cliente reclamó un error de facturación del año pasado.');
});

test('a note that affects inventory needs a warehouse on every line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, ['affects_inventory' => 'yes']),
        )
        ->assertSessionHasErrors('lines.0.warehouse_id');

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
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
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
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
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $stray = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'lines' => [
                    ['item_id' => $item->id, 'measurement_unit_id' => $stray->id, 'quantity' => 1, 'unit_price' => 10],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('the tax chosen for a line is kept with its percentages', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    $payload = salesCreditNotePayload($client, $item, $unit, [
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
        ->post(route('sales-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesCreditNote::with('lines')->find($payload['id'])->lines->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_percent)->toBe(16.0);
    expect((float) $line->tax_amount)->toBe(16.0);
    expect((float) $line->withholding_amount)->toBe(12.0);
});

test('a note can correct an invoice and trace its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = salesCreditNotePayload($client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 25,
                'sales_invoice_line_id' => $invoiceLine->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $note = SalesCreditNote::with('lines')->find($payload['id']);

    expect($note->sales_invoice_id)->toBe($invoice->id);
    expect($note->lines->first()->sales_invoice_line_id)->toBe($invoiceLine->id);
});

test('a note without an invoice cannot credit an invoice line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 25,
                        'sales_invoice_line_id' => $invoice->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.sales_invoice_line_id');
});

test('an invoice of another client cannot be corrected by the note', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);
    $invoice = createSalesInvoice($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('sales_invoice_id');

    expect(SalesCreditNote::count())->toBe(0);
});

test('an invoice of another company cannot be corrected by the note', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $foreign = SalesInvoice::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_invoice_id' => $foreign->id,
            ]),
        )
        ->assertSessionHasErrors('sales_invoice_id');
});

test('a cancelled invoice cannot be credited', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    SalesInvoice::where('id', $invoice->id)->update(['status' => 'cancelled']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('sales_invoice_id');
});

test('a line of another invoice cannot be credited', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $other = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 25,
                        'sales_invoice_line_id' => $other->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.sales_invoice_line_id');
});

test('the credited quantity cannot exceed the invoiced one', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    /** La factura del escenario trae una línea de 2 unidades. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $creditLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 25,
        'sales_invoice_line_id' => $invoiceLine->id,
    ];

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
                'lines' => [$creditLine(3)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    expect(SalesCreditNote::count())->toBe(0);
});

test('what another note already credited lowers the remaining quantity', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    /** 10 unidades facturadas a 100: da margen para dos notas. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]],
    ]);
    $invoiceLine = $invoice->lines->first();

    $creditLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 25,
        'sales_invoice_line_id' => $invoiceLine->id,
    ];

    createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$creditLine(7)],
    ]);

    /** De las 10 facturadas quedan 3: pedir 4 sobra. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
                'lines' => [$creditLine(4)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    $second = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$creditLine(3)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(3.0);
});

test('the note cannot credit more than the invoice is worth', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    /** La factura del escenario vale 200: 2 × 100. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
                'lines' => [[
                    'item_id' => $item->id,
                    'measurement_unit_id' => $unit->id,
                    'quantity' => 1,
                    'unit_price' => 250,
                ]],
            ]),
        )
        ->assertSessionHasErrors('sales_invoice_id');

    expect(SalesCreditNote::count())->toBe(0);
});

test('a cancelled note gives its credited quantity back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $creditLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 25,
        'sales_invoice_line_id' => $invoiceLine->id,
    ];

    $first = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$creditLine(2)],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $first->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $second = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$creditLine(2)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(2.0);
});

test('a user without permission cannot create a sales credit note', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();
    assignRoleWithPermissions($user, $company, ['sales-credit-notes.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit),
        )
        ->assertForbidden();
});

test('the form only offers the active taxes of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id]);
    Tax::factory()->inactive()->create(['company_id' => $company->id]);
    Tax::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-credit-notes/create')
                ->has('options.taxes', 1)
                /* Ni los artículos, ni los clientes, ni las facturas viajan. */
                ->missing('options.items')
                ->missing('options.clients')
                ->missing('options.salesInvoices'),
        );
});

test('an inactive item cannot be credited', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $inactive = Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'inactive',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'lines' => [
                    ['item_id' => $inactive->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.item_id');
});
