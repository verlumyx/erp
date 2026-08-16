<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Tax\Models\Tax;

use function Pest\Laravel\actingAs;

test('an item can be updated', function () {
    [$user, $company, $unit] = itemScenario();

    $item = Item::factory()->create(['company_id' => $company->id, 'sku' => 'SKU-OLD']);
    ItemUnit::factory()->base()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $payload = itemPayload($unit, [
        'sku' => 'SKU-NEW',
        'name' => 'Nombre actualizado',
        'notes' => 'Se cambió el proveedor',
    ]);
    unset($payload['id']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload);

    $response->assertRedirect(route('items.show', ['company' => $company->id, 'id' => $item->id]));
    $response->assertSessionHasNoErrors();

    $item->refresh();
    expect($item->sku)->toBe('SKU-NEW');
    expect($item->name)->toBe('Nombre actualizado');
    expect($item->notes)->toBe('Se cambió el proveedor');
    expect($item->code)->not->toBeNull();
});

test('the item keeps its own sku on update', function () {
    [$user, $company, $unit] = itemScenario();

    $item = Item::factory()->create(['company_id' => $company->id, 'sku' => 'SKU-001']);

    $payload = itemPayload($unit);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload)
        ->assertSessionHasNoErrors();
});

test('adding a unit keeps the existing one', function () {
    [$user, $company, $unit] = itemScenario();
    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $item = Item::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->base()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $payload = itemPayload($unit, [
        'sku' => $item->sku,
        'units' => [
            ['measurement_unit_id' => $unit->id, 'is_base' => 'yes', 'conversion_factor' => 1],
            ['measurement_unit_id' => $box->id, 'is_base' => 'no', 'conversion_factor' => 12],
        ],
    ]);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload)
        ->assertSessionHasNoErrors();

    $item->load('units');
    expect($item->units)->toHaveCount(2);
    expect((float) $item->units->firstWhere('measurement_unit_id', $box->id)->conversion_factor)->toBe(12.0);
});

test('a unit removed from the form is deactivated, never deleted', function () {
    [$user, $company, $unit] = itemScenario();
    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $item = Item::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->base()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'measurement_unit_id' => $unit->id,
    ]);
    ItemUnit::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'measurement_unit_id' => $box->id,
        'conversion_factor' => 12,
    ]);

    $payload = itemPayload($unit, ['sku' => $item->sku]);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(ItemUnit::where('item_id', $item->id)->count())->toBe(2);
    expect(ItemUnit::where('item_id', $item->id)->where('measurement_unit_id', $box->id)->first()->status)
        ->toBe('inactive');
});

test('prices are updated and the ones removed are deactivated', function () {
    [$user, $company, $unit] = itemScenario();

    $item = Item::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->base()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $kept = PriceList::factory()->create(['company_id' => $company->id]);
    $dropped = PriceList::factory()->create(['company_id' => $company->id]);

    ItemPrice::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'price_list_id' => $kept->id,
        'price' => 10,
    ]);
    ItemPrice::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'price_list_id' => $dropped->id,
        'price' => 20,
    ]);

    $payload = itemPayload($unit, [
        'sku' => $item->sku,
        'prices' => [
            ['price_list_id' => $kept->id, 'price' => 99.5, 'currency' => 'USD'],
        ],
    ]);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(ItemPrice::where('item_id', $item->id)->count())->toBe(2);

    $keptPrice = ItemPrice::where('item_id', $item->id)->where('price_list_id', $kept->id)->first();
    expect((float) $keptPrice->price)->toBe(99.5);
    expect($keptPrice->status)->toBe('active');

    expect(ItemPrice::where('item_id', $item->id)->where('price_list_id', $dropped->id)->first()->status)
        ->toBe('inactive');
});

test('the sku cannot collide with another item of the same company', function () {
    [$user, $company, $unit] = itemScenario();

    Item::factory()->create(['company_id' => $company->id, 'sku' => 'SKU-TAKEN']);
    $item = Item::factory()->create(['company_id' => $company->id]);

    $payload = itemPayload($unit, ['sku' => 'SKU-TAKEN']);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload)
        ->assertSessionHasErrors('sku');
});

test('the sale and purchase taxes can be changed and cleared', function () {
    [$user, $company, $unit] = itemScenario();

    $oldTax = Tax::factory()->create(['company_id' => $company->id]);
    $newTax = Tax::factory()->create(['company_id' => $company->id]);

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'sale_tax_id' => $oldTax->id,
        'purchase_tax_id' => $oldTax->id,
    ]);

    $payload = itemPayload($unit, ['sale_tax_id' => $newTax->id]);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload)
        ->assertSessionHasNoErrors();

    $item->refresh();
    expect($item->sale_tax_id)->toBe($newTax->id);
    expect($item->purchase_tax_id)->toBeNull();
});

test('an item from another company cannot be updated', function () {
    [$user, $company, $unit] = itemScenario();

    $foreignItem = Item::factory()->create();

    $payload = itemPayload($unit);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $foreignItem->id]), $payload)
        ->assertNotFound();
});

test('a user without permission cannot update an item', function () {
    [$user, $company, $unit] = itemScenario();
    assignRoleWithPermissions($user, $company, ['items.list']);

    $item = Item::factory()->create(['company_id' => $company->id]);

    $payload = itemPayload($unit);
    unset($payload['id']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update', ['company' => $company->id, 'id' => $item->id]), $payload)
        ->assertForbidden();
});
