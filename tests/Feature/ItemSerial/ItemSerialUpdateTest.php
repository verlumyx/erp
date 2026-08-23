<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\Warehouse\Models\Warehouse;

use function Pest\Laravel\actingAs;

test('a serial can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $serial = ItemSerial::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'serial_number' => 'SN-OLD',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update', ['company' => $company->id, 'id' => $serial->id]), [
            'serial_number' => 'SN-NEW',
            'lot_id' => $lot->id,
            'warehouse_id' => $warehouse->id,
        ]);

    $response->assertRedirect(route('item-serials.show', ['company' => $company->id, 'id' => $serial->id]));
    $response->assertSessionHasNoErrors();

    $serial->refresh();
    expect($serial->serial_number)->toBe('SN-NEW');
    expect($serial->lot_id)->toBe($lot->id);
    expect($serial->warehouse_id)->toBe($warehouse->id);
    /** El artículo de la serie no se cambia desde el formulario. */
    expect($serial->item_id)->toBe($item->id);
});

test('the update rejects a lot of another item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $otherItem = Item::factory()->create(['company_id' => $company->id]);
    $foreignLot = ItemLot::factory()->create(['item_id' => $otherItem->id, 'company_id' => $company->id]);
    $serial = ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update', ['company' => $company->id, 'id' => $serial->id]), [
            'serial_number' => $serial->serial_number,
            'lot_id' => $foreignLot->id,
        ])->assertSessionHasErrors('lot_id');
});

test('the update keeps the serial number unique within the item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'serial_number' => 'SN-TAKEN']);
    $serial = ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'serial_number' => 'SN-MINE']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update', ['company' => $company->id, 'id' => $serial->id]), [
            'serial_number' => 'SN-TAKEN',
        ])->assertSessionHasErrors('serial_number');
});
