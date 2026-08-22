<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a sales order can be created with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'client_reference' => 'OC-2026-0341',
        'payment_term_days' => 30,
        'notes' => 'Entregar antes del mediodía.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('sales-orders.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $order = SalesOrder::with('lines')->find($payload['id']);
    expect($order)->not->toBeNull();
    expect($order->code)->toBe('OVE000001');
    expect($order->status)->toBe('draft');
    expect($order->company_id)->toBe($company->id);
    expect($order->created_by)->toBe($user->id);
    expect($order->client_reference)->toBe('OC-2026-0341');
    expect($order->payment_term_days)->toBe(30);
    expect($order->lines)->toHaveCount(1);

    $line = $order->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(2.0);
    expect((float) $line->base_quantity)->toBe(2.0);
    expect((float) $line->pending_quantity)->toBe(2.0);
    expect((float) $line->reserved_quantity)->toBe(0.0);
    expect($line->status)->toBe('active');
});

test('the totals are computed from the lines and never taken from the payload', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        /** Importes inventados: el backend los ignora y recalcula. */
        'subtotal' => 999999,
        'total' => 999999,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 3,
                'unit_price' => 50,
                'discount_percent' => 10,
                'tax_percent' => 16,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $order = SalesOrder::with('lines')->find($payload['id']);
    $line = $order->lines->first();

    // 3 * 50 = 150; descuento 10% = 15; subtotal 135; IVA 16% = 21.60; total 156.60
    expect((float) $line->discount_amount)->toBe(15.0);
    expect((float) $line->subtotal)->toBe(135.0);
    expect((float) $line->tax_amount)->toBe(21.6);
    expect((float) $line->total)->toBe(156.6);

    expect((float) $order->subtotal)->toBe(135.0);
    expect((float) $order->discount_amount)->toBe(15.0);
    expect((float) $order->tax_amount)->toBe(21.6);
    expect((float) $order->total)->toBe(156.6);
});

test('the note of a line is stored', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 30,
                'notes' => 'Empacar por separado.',
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesOrder::with('lines')->find($payload['id'])->lines->first();
    expect($line->notes)->toBe('Empacar por separado.');
});

test('the line withholding is computed over the subtotal and does not change the total', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 3,
                'unit_price' => 50,
                'discount_percent' => 10,
                'tax_percent' => 16,
                'withholding_percent' => 75,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesOrder::with('lines')->find($payload['id'])->lines->first();

    // Base 135; IVA 16% = 21.60; retención 75% de la base = 101.25; total 156.60
    expect((float) $line->withholding_percent)->toBe(75.0);
    expect((float) $line->withholding_amount)->toBe(101.25);
    expect((float) $line->total)->toBe(156.6);
});

test('the base quantity uses the conversion factor of the line unit', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $box->id,
                'quantity' => 2,
                'unit_price' => 100,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesOrder::with('lines')->find($payload['id'])->lines->first();
    expect((float) $line->quantity)->toBe(2.0);
    expect((float) $line->base_quantity)->toBe(24.0);
});

test('the code auto-increments per company', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $first = salesOrderPayload($client, $warehouse, $item, $unit);
    $second = salesOrderPayload($client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $first);
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $second);

    expect(SalesOrder::find($first['id'])->code)->toBe('OVE000001');
    expect(SalesOrder::find($second['id'])->code)->toBe('OVE000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA, $clientA, $warehouseA, $itemA, $unitA] = salesOrderScenario();
    [$userB, $companyB, $clientB, $warehouseB, $itemB, $unitB] = salesOrderScenario();

    $payloadA = salesOrderPayload($clientA, $warehouseA, $itemA, $unitA);
    $payloadB = salesOrderPayload($clientB, $warehouseB, $itemB, $unitB);

    actingAs($userA)->withSession(['current_company_id' => $companyA->id])
        ->post(route('sales-orders.store', ['company' => $companyA->id]), $payloadA);
    actingAs($userB)->withSession(['current_company_id' => $companyB->id])
        ->post(route('sales-orders.store', ['company' => $companyB->id]), $payloadB);

    expect(SalesOrder::find($payloadA['id'])->code)->toBe('OVE000001');
    expect(SalesOrder::find($payloadB['id'])->code)->toBe('OVE000001');
});

test('the order requires at least one line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, ['lines' => []]),
        )
        ->assertSessionHasErrors('lines');
});

test('the line quantity must be greater than zero', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 0,
                        'unit_price' => 100,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.quantity');
});

test('the same item cannot be repeated with the same unit', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $line = [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 1,
        'unit_price' => 100,
    ];

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, ['lines' => [$line, $line]]),
        )
        ->assertSessionHasErrors('lines.1.item_id');
});

test('a client from another company is rejected', function () {
    [$user, $company, , $warehouse, $item, $unit] = salesOrderScenario();

    $foreignClient = Client::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($foreignClient, $warehouse, $item, $unit),
        )
        ->assertSessionHasErrors('client_id');
});

test('a warehouse from another company is rejected', function () {
    [$user, $company, $client, , $item, $unit] = salesOrderScenario();

    $foreignWarehouse = Warehouse::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $foreignWarehouse, $item, $unit),
        )
        ->assertSessionHasErrors('warehouse_id');
});

test('a non sellable item is rejected', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesOrderScenario();

    $notSellable = Item::factory()->create([
        'company_id' => $company->id,
        'is_sellable' => 'no',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $notSellable, $unit),
        )
        ->assertSessionHasErrors('lines.0.item_id');
});

test('a delivery address of another client is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $otherClient = Client::factory()->create(['company_id' => $company->id]);
    $foreignAddress = ClientAddress::factory()->create([
        'company_id' => $company->id,
        'client_id' => $otherClient->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'client_address_id' => $foreignAddress->id,
            ]),
        )
        ->assertSessionHasErrors('client_address_id');
});

test('the expected date cannot be earlier than the order date', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'order_date' => '2026-08-16',
                'expected_date' => '2026-08-10',
            ]),
        )
        ->assertSessionHasErrors('expected_date');
});

test('the currency must exist in the global catalog', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, ['currency' => 'XXX']),
        )
        ->assertSessionHasErrors('currency');
});

test('a user without permission cannot create a sales order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();
    assignRoleWithPermissions($user, $company, ['sales-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});

test('the form only offers the active taxes of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id]);
    Tax::factory()->inactive()->create(['company_id' => $company->id]);
    Tax::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-orders/create')
                ->has('options.taxes', 1),
        );
});

test('the tax chosen for a line is kept with its percentages', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $tax = Tax::factory()->withWithholding(75)->create([
        'company_id' => $company->id,
        'percentage' => 16,
    ]);

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
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
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesOrder::with('lines')->find($payload['id'])->lines->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_percent)->toBe(16.0);
    expect((float) $line->tax_amount)->toBe(16.0);
    expect((float) $line->withholding_percent)->toBe(75.0);
});

test('a tax of another company cannot be used in a line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $foreignTax = Tax::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'tax_id' => $foreignTax->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.tax_id');
});

test('an inactive tax cannot be used in a line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $tax = Tax::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'tax_id' => $tax->id,
                    ],
                ],
            ]),
        )
        ->assertSessionHasErrors('lines.0.tax_id');
});
