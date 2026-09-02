<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Models\SalesOrder;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail carries the dispatch with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dispatches/show')
            ->where('dispatch.code', 'DES000001')
            ->where('dispatch.recipient_name', $client->name)
            ->where('dispatch.warehouse_name', $warehouse->name)
            ->where('dispatch.delivery_status', 'pending')
            ->has('dispatch.lines', 1)
            ->where('dispatch.lines.0.item_name', $item->name));
});

test('the detail names the source order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('dispatch.sourceable_type', 'sales_order')
            ->where('dispatch.sourceable_code', $order->code));
});

test('the edit screen carries the dispatch and the catalogs', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.edit', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dispatches/edit')
            ->where('dispatch.id', $dispatch->id)
            ->has('options.warehouses'));
});

test('the detail requires the show permission', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['dispatches.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertForbidden();
});

test('the lots and serials of a line travel as lists, not wrapped in data', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    \App\Modules\Item\Models\Item::where('id', $item->id)->update(['type' => 'serialized']);

    $lot = \App\Modules\ItemLot\Models\ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'created_by' => $user->id,
    ]);

    $serials = \App\Modules\ItemSerial\Models\ItemSerial::factory()->count(2)->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'lots' => [['lot_id' => $lot->id, 'quantity' => 2]],
            'serials' => $serials->map(fn ($serial): array => ['serial_id' => $serial->id])->all(),
        ]],
    ]);

    $props = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->viewData('page')['props'];

    $line = $props['dispatch']['lines'][0];

    /**
     * Una colección de recursos sin resolver se serializa como `{data: [...]}`,
     * y la pantalla la recorre con `.filter()`: tiene que ser una lista.
     */
    expect(array_is_list($line['lots']))->toBeTrue();
    expect(array_is_list($line['serials']))->toBeTrue();

    expect($line['lots'])->toHaveCount(1);
    expect($line['serials'])->toHaveCount(2);
});

test('a line without traceability carries empty lists', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    $props = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['dispatch']['lines'][0]['lots'])->toBe([]);
    expect($props['dispatch']['lines'][0]['serials'])->toBe([]);
});
