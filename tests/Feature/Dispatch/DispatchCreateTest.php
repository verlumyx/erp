<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a dispatch can be created', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'vehicle_plate' => 'AB123CD',
        'carrier' => 'Transporte Andino',
        'tracking_number' => 'GUIA-0001',
        'freight_amount' => 25.5,
        'notes' => 'Entregar antes del mediodía.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('dispatches.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $dispatch = Dispatch::with('lines')->find($payload['id']);
    expect($dispatch)->not->toBeNull();
    expect($dispatch->code)->toBe('DES000001');
    expect($dispatch->status)->toBe('draft');
    /** Nace en la bodega: todavía no está en la calle. */
    expect($dispatch->delivery_status)->toBe('pending');
    expect($dispatch->company_id)->toBe($company->id);
    expect($dispatch->created_by)->toBe($user->id);
    expect($dispatch->client_id)->toBe($client->id);
    expect($dispatch->warehouse_id)->toBe($warehouse->id);
    expect($dispatch->carrier)->toBe('Transporte Andino');
    expect((float) $dispatch->freight_amount)->toBe(25.5);
    /** Un despacho directo no viene de ningún pedido. */
    expect($dispatch->sourceable_type)->toBeNull();
    expect($dispatch->sourceable_id)->toBeNull();

    expect($dispatch->lines)->toHaveCount(1);
    $line = $dispatch->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(2.0);
    expect((float) $line->base_quantity)->toBe(2.0);
    /** El viaje aún no ha terminado: nada entregado, nada devuelto. */
    expect((float) $line->delivered_quantity)->toBe(0.0);
    expect((float) $line->returned_quantity)->toBe(0.0);
});

test('the code is sequential per company', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    createDispatch($user, $company, $client, $warehouse, $item, $unit);
    $second = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    expect($second->code)->toBe('DES000002');
});

test('the header totals are derived from the lines and the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    Item::where('id', $item->id)->update([
        'weight' => 2.5,
        'volume' => 0.4,
        'average_cost' => 30,
    ]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 4, 'unit_price' => 100],
        ],
    ]);

    expect((float) $dispatch->total_quantity)->toBe(4.0);
    expect((float) $dispatch->total_weight)->toBe(10.0);
    expect((float) $dispatch->total_volume)->toBe(1.6);
    /** Mientras es borrador, la carga se valora al promedio vigente. */
    expect((float) $dispatch->total_cost)->toBe(120.0);
    expect((float) $dispatch->lines->first()->unit_cost)->toBe(30.0);
});

test('the line amounts are calculated on the backend and ignore what the client sends', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
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

    $line = $dispatch->lines->first();

    // 10 × 100 = 1000, −10 % = 900 de base; 16 % de impuesto = 144.
    expect((float) $line->discount_amount)->toBe(100.0);
    expect((float) $line->subtotal)->toBe(900.0);
    expect((float) $line->tax_amount)->toBe(144.0);
    expect((float) $line->total)->toBe(1044.0);
    /** La retención se practica sobre el impuesto, no sobre la base. */
    expect((float) $line->withholding_amount)->toBe(108.0);
});

test('base_quantity converts the line to the base unit of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $box->id, 'quantity' => 5, 'unit_price' => 10],
        ],
    ]);

    expect((float) $dispatch->lines->first()->base_quantity)->toBe(60.0);
});

test('a line whose unit is not registered for the item is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $stranger = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $stranger->id, 'quantity' => 1, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('a location from another warehouse is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $other = Warehouse::factory()->create(['company_id' => $company->id, 'uses_locations' => 'yes']);
    $stranger = WarehouseLocation::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $other->id,
    ]);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 10,
                'location_id' => $stranger->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.location_id');
});

test('a dispatch without lines is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $payload = dispatchPayload($client, $warehouse, $item, $unit, ['lines' => []]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines');
});

test('a client from another company is rejected', function () {
    [$user, $company, , $warehouse, $item, $unit] = dispatchScenario();

    [, $otherCompany] = createUserWithCompany();
    $stranger = Client::factory()->create(['company_id' => $otherCompany->id]);

    $payload = dispatchPayload($stranger, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('client_id');
});

test('a serialized item dispatches exactly one unit per line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    Item::where('id', $item->id)->update(['type' => ItemSerial::TRACKABLE_ITEM_TYPE]);

    $serial = ItemSerial::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 3,
                'unit_price' => 10,
                'serial_id' => $serial->id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.quantity');
});

test('a serialized item without its serial is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    Item::where('id', $item->id)->update(['type' => ItemSerial::TRACKABLE_ITEM_TYPE]);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.serial_id');
});

test('creating requires the create permission', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    assignRoleWithPermissions($user, $company, ['dispatches.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('dispatches.store', ['company' => $company->id]),
            dispatchPayload($client, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});

test('the create screen carries the catalogs the form needs', function () {
    [$user, $company] = dispatchScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dispatches/create')
            ->has('options.warehouses')
            ->has('options.locations')
            ->has('options.taxes')
            ->has('options.drivers'));
});
