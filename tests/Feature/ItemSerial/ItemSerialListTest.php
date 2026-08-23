<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemSerial\Exceptions\ItemSerialNotFoundException;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the list only shows serials of the active company', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'serial_number' => 'MINE']);
    ItemSerial::factory()->create(['serial_number' => 'THEIRS']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-serials.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('item-serials/index')
        ->has('serials', 1)
        ->where('serials.0.serial_number', 'MINE')
        ->has('warehouses')
    );
});

test('the list can be filtered by status and by warehouse', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    ItemSerial::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'serial_number' => 'DISPONIBLE',
    ]);
    ItemSerial::factory()->sold()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'serial_number' => 'VENDIDA',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-serials.index', ['company' => $company->id, 'status' => 'sold']))
        ->assertInertia(fn ($page) => $page->has('serials', 1)->where('serials.0.serial_number', 'VENDIDA'));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-serials.index', ['company' => $company->id, 'warehouse_id' => $warehouse->id]))
        ->assertInertia(fn ($page) => $page->has('serials', 1)->where('serials.0.serial_number', 'DISPONIBLE'));
});

test('a serial shows its item, lot and warehouse', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized', 'name' => 'Laptop']);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Central']);
    $serial = ItemSerial::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'serial_number' => 'SN-VER',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-serials.show', ['company' => $company->id, 'id' => $serial->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('item-serials/show')
            ->where('serial.serial_number', 'SN-VER')
            ->where('serial.item_name', 'Laptop')
            ->where('serial.warehouse_name', 'Central')
        );
});

test('showing a missing serial throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('item-serials.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(ItemSerialNotFoundException::class);

test('a serial from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = ItemSerial::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('item-serials.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(ItemSerialNotFoundException::class);
