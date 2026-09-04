<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a purchase return can be created', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'reason' => 'wrong_item',
        'carrier' => 'Transporte Zoom',
        'tracking_number' => 'ZM-88771',
        'notes' => 'El proveedor despachó otra referencia.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('purchase-returns.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $return = PurchaseReturn::with('lines')->find($payload['id']);
    expect($return)->not->toBeNull();
    expect($return->code)->toBe('DVC000001');
    expect($return->status)->toBe('draft');
    expect($return->company_id)->toBe($company->id);
    expect($return->created_by)->toBe($user->id);
    expect($return->supplier_id)->toBe($supplier->id);
    expect($return->warehouse_id)->toBe($warehouse->id);
    expect($return->reason)->toBe('wrong_item');
    expect($return->carrier)->toBe('Transporte Zoom');
    expect($return->tracking_number)->toBe('ZM-88771');
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
     * Sin línea de factura detrás el costo cae al promedio del artículo, que
     * en un artículo estrenado es cero: la pantalla no lo captura.
     */
    expect((float) $line->subtotal)->toBe(0.0);
});

test('the amounts come from the invoiced line and ignore what the client sends', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    /** Una factura de 10 a 100, con 10 % de descuento y su impuesto. */
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
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

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'subtotal' => 999999,
        'total' => 999999,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 10,
                'purchase_invoice_line_id' => $invoice->lines->first()->id,
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
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = PurchaseReturn::with('lines')->find($payload['id']);
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
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $box->id, 'quantity' => 5, 'warehouse_id' => $warehouse->id],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = PurchaseReturn::with('lines')->find($payload['id'])->lines->first();
    expect((float) $line->quantity)->toBe(5.0);
    expect((float) $line->base_quantity)->toBe(60.0);
});

test('the code auto-increments per company', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $first = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);
    $second = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    expect($first->code)->toBe('DVC000001');
    expect($second->code)->toBe('DVC000002');
});

test('the return requires at least one line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['lines' => []]),
        )
        ->assertSessionHasErrors('lines');
});

test('the supplier, the warehouse, the date and the reason are required', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'supplier_id' => '',
                'warehouse_id' => '',
                'return_date' => '',
                'reason' => '',
            ]),
        )
        ->assertSessionHasErrors(['supplier_id', 'warehouse_id', 'return_date', 'reason']);
});

test('a supplier or a warehouse from another company is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'supplier_id' => Supplier::factory()->create()->id,
                'warehouse_id' => Warehouse::factory()->create()->id,
            ]),
        )
        ->assertSessionHasErrors(['supplier_id', 'warehouse_id']);
});

test('a reason outside the catalog is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['reason' => 'porque_si']),
        )
        ->assertSessionHasErrors('reason');
});

test('the reason detail is required when the reason is other', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['reason' => 'other']),
        )
        ->assertSessionHasErrors('reason_detail');

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'reason' => 'other',
        'reason_detail' => 'El proveedor cambió la presentación sin avisar.',
    ]);

    expect($return->reason)->toBe('other');
    expect($return->reason_detail)->toBe('El proveedor cambió la presentación sin avisar.');
});

test('a line can carry its own reason', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
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
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $stray = Warehouse::factory()->create();

    $line = fn (array $extra): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 1,
        ...$extra,
    ];

    /** Sin bodega no se sabe de dónde sale la mercancía. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['lines' => [$line([])]]),
        )
        ->assertSessionHasErrors('lines.0.warehouse_id');

    /** Y una de otra empresa tampoco vale. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'lines' => [$line(['warehouse_id' => $stray->id])],
            ]),
        )
        ->assertSessionHasErrors('lines.0.warehouse_id');

    /** Una línea puede salir de otra bodega de la empresa, no solo de la cabecera. */
    $second = Warehouse::factory()->create(['company_id' => $company->id]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [$line(['warehouse_id' => $second->id])],
    ]);

    expect($return->lines->first()->warehouse_id)->toBe($second->id);
});

test('the line unit must belong to the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $stray = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'lines' => [
                    ['item_id' => $item->id, 'measurement_unit_id' => $stray->id, 'quantity' => 1, 'warehouse_id' => $warehouse->id],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('the tax of a line comes from the invoiced line, not from the screen', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
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

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 1,
                'purchase_invoice_line_id' => $invoice->lines->first()->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = PurchaseReturn::with('lines')->find($payload['id'])->lines->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_percent)->toBe(16.0);
    expect((float) $line->tax_amount)->toBe(16.0);
    expect((float) $line->withholding_amount)->toBe(12.0);
});

test('a return can come from an invoice and trace its lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 3,
                'warehouse_id' => $warehouse->id,
                'purchase_invoice_line_id' => $invoiceLine->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = PurchaseReturn::with('lines')->find($payload['id']);

    expect($return->purchase_invoice_id)->toBe($invoice->id);
    expect($return->lines->first()->purchase_invoice_line_id)->toBe($invoiceLine->id);
});

test('a return without an invoice cannot return an invoice line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'warehouse_id' => $warehouse->id,
                        'purchase_invoice_line_id' => $invoice->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.purchase_invoice_line_id');
});

test('an invoice of another supplier cannot be the origin of the return', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);
    $invoice = createPurchaseInvoice($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_invoice_id');

    expect(PurchaseReturn::count())->toBe(0);
});

test('an invoice of another company cannot be the origin of the return', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $foreign = PurchaseInvoice::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'purchase_invoice_id' => $foreign->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_invoice_id');
});

test('a cancelled invoice cannot be returned', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    PurchaseInvoice::where('id', $invoice->id)->update(['status' => 'cancelled']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_invoice_id');
});

test('a line of another invoice cannot be returned', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $other = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'warehouse_id' => $warehouse->id,
                        'purchase_invoice_line_id' => $other->lines->first()->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.purchase_invoice_line_id');
});

test('the returned quantity cannot exceed the invoiced one', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    /** La factura del escenario trae una línea de 10 unidades. */
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $returnLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'warehouse_id' => $warehouse->id,
        'purchase_invoice_line_id' => $invoiceLine->id,
    ];

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
                'lines' => [$returnLine(11)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    expect(PurchaseReturn::count())->toBe(0);
});

test('what another return already took lowers the remaining quantity', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $returnLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'warehouse_id' => $warehouse->id,
        'purchase_invoice_line_id' => $invoiceLine->id,
    ];

    createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$returnLine(7)],
    ]);

    /** De las 10 facturadas quedan 3: pedir 4 sobra. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'purchase_invoice_id' => $invoice->id,
                'lines' => [$returnLine(4)],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');

    $second = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$returnLine(3)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(3.0);
});

test('a cancelled return gives its quantity back', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $returnLine = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'warehouse_id' => $warehouse->id,
        'purchase_invoice_line_id' => $invoiceLine->id,
    ];

    $first = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$returnLine(10)],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $first->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $second = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$returnLine(10)],
    ]);

    expect((float) $second->lines->first()->quantity)->toBe(10.0);
});

test('the lot is no longer captured in the return: the dispatch asks for it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $lot = ItemLot::factory()->create(['company_id' => $company->id, 'item_id' => $item->id]);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();
    PurchaseInvoiceLine::where('id', $invoiceLine->id)->update(['lot_id' => $lot->id]);

    /**
     * La devolución de una línea con lote ya no exige el lote: quien prepara el
     * retiro lo elige en el despacho que la devolución genera.
     */
    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'purchase_invoice_line_id' => $invoiceLine->id,
            /** Y un lote que mande el cliente se ignora: no es un dato suyo. */
            'lot_id' => $lot->id,
        ]],
    ]);

    expect($return->lines->first()->lot_id)->toBeNull();
});

test('a serialized item is returned without naming its serial', function () {
    [$user, $company, $supplier, $warehouse, , $unit] = purchaseReturnScenario();

    $serialized = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'serialized',
        'is_purchasable' => 'yes',
    ]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $serialized->id,
        'measurement_unit_id' => $unit->id,
    ]);

    /**
     * La serie la exige el despacho al confirmarse, no la devolución: aquí solo
     * se acuerda qué vuelve y cuánto.
     */
    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $serialized, $unit, [
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
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $stray = ItemSerial::factory()->create(['company_id' => $company->id]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
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

test('a user without permission cannot create a purchase return', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();
    assignRoleWithPermissions($user, $company, ['purchase-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});

test('the form only offers the active catalogs of the active company', function () {
    [$user, $company] = purchaseReturnScenario();

    Warehouse::factory()->inactive()->create(['company_id' => $company->id]);
    Warehouse::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-returns/create')
                ->has('options.warehouses', 1)
                /* Ni los artículos, ni los proveedores, ni las facturas viajan. */
                ->missing('options.items')
                ->missing('options.suppliers')
                ->missing('options.purchaseInvoices')
                /* Ni los impuestos ni las ubicaciones: la línea ya no los captura. */
                ->missing('options.taxes')
                ->missing('options.locations')
                ->missing('options.lots')
                ->missing('options.serials'),
        );
});

test('an inactive item cannot be returned', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $inactive = Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'inactive',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-returns.store', ['company' => $company->id]),
            purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
                'lines' => [
                    ['item_id' => $inactive->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'warehouse_id' => $warehouse->id],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.item_id');
});
