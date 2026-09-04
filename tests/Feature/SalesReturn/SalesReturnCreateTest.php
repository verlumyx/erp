<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a sales return can be created', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'reason' => 'wrong_item',
        'condition' => 'resalable',
        'notes' => 'El cliente despachó otra referencia.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('sales-returns.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $return = SalesReturn::with('lines')->find($payload['id']);
    expect($return)->not->toBeNull();
    expect($return->code)->toBe('DVV000001');
    expect($return->status)->toBe('draft');
    expect($return->company_id)->toBe($company->id);
    expect($return->created_by)->toBe($user->id);
    expect($return->client_id)->toBe($client->id);
    expect($return->warehouse_id)->toBe($warehouse->id);
    expect($return->reason)->toBe('wrong_item');
    expect($return->condition)->toBe('resalable');
    /** La nota de crédito llega después: la devolución nace sin acreditar. */
    expect($return->credit_note_id)->toBeNull();
    expect($return->lines)->toHaveCount(1);

    $line = $return->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(2.0);
    expect((float) $line->base_quantity)->toBe(2.0);
    expect($line->warehouse_id)->toBe($warehouse->id);
    /**
     * Sin línea de factura detrás el precio cae al promedio del artículo, que
     * en un artículo estrenado es cero: la pantalla no lo captura.
     */
    expect((float) $line->subtotal)->toBe(0.0);
});

test('the amounts come from the invoiced line and ignore what the client sends', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    /** Una factura de 10 a 100, con 10 % de descuento y su impuesto. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
                'discount_percent' => 10,
                'tax_id' => $tax->id,
                'tax_percent' => 16,
                'withholding_percent' => 75,
            ],
        ],
    ]);

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'subtotal' => 999999,
        'total' => 999999,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 10,
                'sales_invoice_line_id' => $invoice->lines->first()->id,
                /** Lo que mande la pantalla no cuenta: el precio sale de la factura. */
                'unit_price' => 1,
                'discount_percent' => 90,
                'tax_percent' => 99,
                'subtotal' => 1,
                'total' => 1,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = SalesReturn::with('lines')->find($payload['id']);
    $line = $return->lines->first();

    // 10 × 100 = 1000, −10 % = 900 de base; 16 % de impuesto = 144.
    expect((float) $line->unit_price)->toBe(100.0);
    expect((float) $line->discount_amount)->toBe(100.0);
    expect((float) $line->subtotal)->toBe(900.0);
    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_amount)->toBe(144.0);
    expect((float) $line->total)->toBe(1044.0);
    /** La retención se practica sobre el impuesto, no sobre la base. */
    expect((float) $line->withholding_amount)->toBe(108.0);

    expect((float) $return->subtotal)->toBe(900.0);
    expect((float) $return->tax_amount)->toBe(144.0);
    expect((float) $return->total)->toBe(1044.0);
});

test('base_quantity converts the line to the base unit of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $box->id, 'quantity' => 5, 'warehouse_id' => $warehouse->id],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesReturn::with('lines')->find($payload['id'])->lines->first();
    expect((float) $line->quantity)->toBe(5.0);
    expect((float) $line->base_quantity)->toBe(60.0);
});

test('the code auto-increments per company', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $first = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);
    $second = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    expect($first->code)->toBe('DVV000001');
    expect($second->code)->toBe('DVV000002');
});

test('the return requires at least one line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, ['lines' => []]),
        )
        ->assertSessionHasErrors('lines');
});

test('the client, the warehouse, the date and the reason are required', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'client_id' => '',
                'warehouse_id' => '',
                'return_date' => '',
                'reason' => '',
            ]),
        )
        ->assertSessionHasErrors(['client_id', 'warehouse_id', 'return_date', 'reason']);
});

test('a client or a warehouse from another company is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'client_id' => Client::factory()->create()->id,
                'warehouse_id' => Warehouse::factory()->create()->id,
            ]),
        )
        ->assertSessionHasErrors(['client_id', 'warehouse_id']);
});

test('a reason outside the catalog is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, ['reason' => 'porque_si']),
        )
        ->assertSessionHasErrors('reason');
});

test('the reason detail is required when the reason is other', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, ['reason' => 'other']),
        )
        ->assertSessionHasErrors('reason_detail');

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'reason' => 'other',
        'reason_detail' => 'El cliente cambió la presentación sin avisar.',
    ]);

    expect($return->reason)->toBe('other');
    expect($return->reason_detail)->toBe('El cliente cambió la presentación sin avisar.');
});

test('a line can carry its own reason', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'reason' => 'damaged',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'warehouse_id' => $warehouse->id,
                'reason' => 'expired',
            ],
        ],
    ]);

    expect($return->reason)->toBe('damaged');
    expect($return->lines->first()->reason)->toBe('expired');
});

test('the line warehouse is required and must be of the company', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $stray = Warehouse::factory()->create();

    $line = fn (array $extra): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 1,
        ...$extra,
    ];

    /** Sin bodega no se sabe a dónde vuelve la mercancía. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, ['lines' => [$line([])]]),
        )
        ->assertSessionHasErrors('lines.0.warehouse_id');

    /** Y una de otra empresa tampoco vale. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'lines' => [$line(['warehouse_id' => $stray->id])],
            ]),
        )
        ->assertSessionHasErrors('lines.0.warehouse_id');

    /** Una línea puede reingresar a otra bodega de la empresa, no solo a la cabecera. */
    $second = Warehouse::factory()->create(['company_id' => $company->id]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [$line(['warehouse_id' => $second->id])],
    ]);

    expect($return->lines->first()->warehouse_id)->toBe($second->id);
});

test('the line unit must belong to the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $stray = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    ['item_id' => $item->id, 'measurement_unit_id' => $stray->id, 'quantity' => 1, 'warehouse_id' => $warehouse->id],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('the tax of a line comes from the invoiced line, not from the screen', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
                'tax_id' => $tax->id,
                'tax_percent' => 16,
                'withholding_percent' => 75,
            ],
        ],
    ]);

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 1,
                'sales_invoice_line_id' => $invoice->lines->first()->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesReturn::with('lines')->find($payload['id'])->lines->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_percent)->toBe(16.0);
    expect((float) $line->tax_amount)->toBe(16.0);
    expect((float) $line->withholding_amount)->toBe(12.0);
});

test('a return can come from an invoice and trace its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'warehouse_id' => $warehouse->id,
                'sales_invoice_line_id' => $invoiceLine->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = SalesReturn::with('lines')->find($payload['id']);

    expect($return->sales_invoice_id)->toBe($invoice->id);
    expect($return->lines->first()->sales_invoice_line_id)->toBe($invoiceLine->id);
});

test('a return without an invoice cannot return an invoice line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'warehouse_id' => $warehouse->id,
                        'sales_invoice_line_id' => $invoice->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.sales_invoice_line_id');
});

test('an invoice of another client cannot be the origin of the return', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);
    $invoice = createSalesInvoice($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('sales_invoice_id');

    expect(SalesReturn::count())->toBe(0);
});

test('an invoice of another company cannot be the origin of the return', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $foreign = SalesInvoice::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'sales_invoice_id' => $foreign->id,
            ]),
        )
        ->assertSessionHasErrors('sales_invoice_id');
});

test('a cancelled invoice cannot be returned', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    SalesInvoice::where('id', $invoice->id)->update(['status' => 'cancelled']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('sales_invoice_id');
});

test('a line of another invoice cannot be returned', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $other = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'warehouse_id' => $warehouse->id,
                        'sales_invoice_line_id' => $other->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.sales_invoice_line_id');
});

test('the returned quantity cannot exceed the invoiced one', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    /** La factura del escenario trae una línea de 2 unidades. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $returnLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'warehouse_id' => $warehouse->id,
        'sales_invoice_line_id' => $invoiceLine->id,
    ];

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
                'lines' => [$returnLine(3)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    expect(SalesReturn::count())->toBe(0);
});

test('what another return already took lowers the remaining quantity', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $returnLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'warehouse_id' => $warehouse->id,
        'sales_invoice_line_id' => $invoiceLine->id,
    ];

    createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$returnLine(1)],
    ]);

    /** De las 2 facturadas queda 1: pedir 2 sobra. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'sales_invoice_id' => $invoice->id,
                'lines' => [$returnLine(2)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    $second = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$returnLine(1)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(1.0);
});

test('a cancelled return gives its quantity back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $returnLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'warehouse_id' => $warehouse->id,
        'sales_invoice_line_id' => $invoiceLine->id,
    ];

    $first = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$returnLine(2)],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $first->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $second = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [$returnLine(2)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(2.0);
});

test('the lot is no longer captured in the return: the entry asks for it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $lot = ItemLot::factory()->create(['company_id' => $company->id, 'item_id' => $item->id]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();
    SalesInvoiceLine::where('id', $invoiceLine->id)->update(['lot_id' => $lot->id]);

    /**
     * La devolución de una línea con lote ya no exige el lote: quien recibe la
     * mercancía lo elige en la entrada que la devolución genera.
     */
    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'sales_invoice_line_id' => $invoiceLine->id,
            /** Y un lote que mande el cliente se ignora: no es un dato suyo. */
            'lot_id' => $lot->id,
        ]],
    ]);

    expect($return->lines->first()->lot_id)->toBeNull();
});

test('a serialized item is returned without naming its serial', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesReturnScenario();

    $serialized = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'serialized',
        'is_sellable' => 'yes',
    ]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $serialized->id,
        'measurement_unit_id' => $unit->id,
    ]);

    /**
     * La serie la exige la entrada al confirmarse, no la devolución: aquí solo
     * se acuerda qué vuelve y cuánto.
     */
    $return = createSalesReturn($user, $company, $client, $warehouse, $serialized, $unit, [
        'lines' => [[
            'item_id' => $serialized->id,
            'measurement_unit_id' => $unit->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
        ]],
    ]);

    expect($return->lines->first()->serial_id)->toBeNull();
});

test('a serial sent from the client is ignored', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $stray = ItemSerial::factory()->create(['company_id' => $company->id]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 1,
                'serial_id' => $stray->id,
            ],
        ],
    ]);

    expect($return->lines->first()->serial_id)->toBeNull();
});

test('a user without permission cannot create a sales return', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();
    assignRoleWithPermissions($user, $company, ['sales-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});

test('the form only offers the active catalogs of the active company', function () {
    [$user, $company] = salesReturnScenario();

    Warehouse::factory()->inactive()->create(['company_id' => $company->id]);
    Warehouse::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-returns/create')
                ->has('options.warehouses', 1)
                ->has('options.receivers')
                /* Ni los artículos, ni los clientes, ni las facturas viajan. */
                ->missing('options.items')
                ->missing('options.clients')
                ->missing('options.salesInvoices')
                /* Ni los impuestos ni las ubicaciones: la línea ya no los captura. */
                ->missing('options.taxes')
                ->missing('options.locations')
                ->missing('options.lots')
                ->missing('options.serials'),
        );
});

test('an inactive item cannot be returned', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $inactive = Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'inactive',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    ['item_id' => $inactive->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'warehouse_id' => $warehouse->id],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.item_id');
});

test('the condition of the goods is required and must be in the catalog', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, ['condition' => '']),
        )
        ->assertSessionHasErrors('condition');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, ['condition' => 'mojada']),
        )
        ->assertSessionHasErrors('condition');
});

test('damaged goods only re-enter a quarantine warehouse', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $quarantine = Warehouse::factory()->usesLocations()->create([
        'company_id' => $company->id,
        'type' => 'quarantine',
    ]);

    /** La bodega de venta no recibe mercancía dañada. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $warehouse, $item, $unit, ['condition' => 'damaged']),
        )
        ->assertSessionHasErrors('warehouse_id');

    $return = createSalesReturn($user, $company, $client, $quarantine, $item, $unit, [
        'condition' => 'damaged',
    ]);

    expect($return->condition)->toBe('damaged');
    expect($return->warehouse_id)->toBe($quarantine->id);
});

test('a quarantine warehouse does not take resalable goods', function () {
    [$user, $company, $client, , $item, $unit] = salesReturnScenario();

    $quarantine = Warehouse::factory()->usesLocations()->create([
        'company_id' => $company->id,
        'type' => 'quarantine',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-returns.store', ['company' => $company->id]),
            salesReturnPayload($client, $quarantine, $item, $unit, ['condition' => 'resalable']),
        )
        ->assertSessionHasErrors('warehouse_id');
});

test('the condition is of the return, not of the line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    /**
     * La condición decide a qué bodega puede volver la mercancía, así que manda
     * la de la cabecera: una condición de línea que mande el cliente se ignora.
     */
    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'condition' => 'resalable',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'condition' => 'scrap',
        ]],
    ]);

    expect($return->condition)->toBe('resalable');
    expect($return->lines->first()->condition)->toBeNull();
});

test('the cost the line re-enters at is the one frozen by the sale', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();
    SalesInvoiceLine::where('id', $invoiceLine->id)->update(['unit_cost' => 33]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            /** El costo no se captura: lo pone el backend, y este viaja para nada. */
            'unit_cost' => 999,
            'sales_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    expect((float) $return->lines->first()->unit_cost)->toBe(33.0);
});

test('without an invoice line the cost falls back to the average of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    Item::where('id', $item->id)->update(['average_cost' => 12.5]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    expect((float) $return->lines->first()->unit_cost)->toBe(12.5);
});
