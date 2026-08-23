<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Commands\CreateItemSerialCommand;
use App\Modules\ItemSerial\Exceptions\DuplicateItemSerialNumberException;
use App\Modules\ItemSerial\Exceptions\ItemSerialLotMismatchException;
use App\Modules\ItemSerial\Exceptions\ItemSerialNotTrackableException;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Services\ItemSerialCreateService;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Support\Str;

/**
 * La serie no se crea desde pantalla: nace en el documento que recibe la
 * mercancía. Estos tests ejercen el servicio que ese documento llamará.
 */
function createSerial(string $companyId, string $itemId, array $overrides = []): ItemSerial
{
    return app(ItemSerialCreateService::class)->execute(new CreateItemSerialCommand(
        id: $overrides['id'] ?? (string) Str::uuid7(),
        companyId: $companyId,
        itemId: $itemId,
        serialNumber: $overrides['serialNumber'] ?? 'SN-0001',
        createdBy: $overrides['createdBy'],
        lotId: $overrides['lotId'] ?? null,
        warehouseId: $overrides['warehouseId'] ?? null,
        status: $overrides['status'] ?? 'available',
    ));
}

test('the service creates a serial for a serialized item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $serial = createSerial($company->id, $item->id, [
        'createdBy' => $user->id,
        'warehouseId' => $warehouse->id,
    ]);

    expect($serial->serial_number)->toBe('SN-0001');
    expect($serial->warehouse_id)->toBe($warehouse->id);
    expect($serial->status)->toBe('available');
    expect($serial->sold_at)->toBeNull();
    expect($serial->company_id)->toBe($company->id);
    expect($serial->created_by)->toBe($user->id);
    expect($serial->code)->toBe('SER000001');
});

test('the service rejects an item that is not serialized', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'inventoried']);

    createSerial($company->id, $item->id, ['createdBy' => $user->id]);
})->throws(ItemSerialNotTrackableException::class);

test('the service rejects an item from another company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $foreign = Item::factory()->create(['company_id' => $otherCompany->id, 'type' => 'serialized']);

    createSerial($company->id, $foreign->id, ['createdBy' => $user->id]);
})->throws(ItemSerialNotTrackableException::class);

test('the service rejects a repeated serial number on the same item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    ItemSerial::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'serial_number' => 'SN-DUP',
    ]);

    createSerial($company->id, $item->id, ['createdBy' => $user->id, 'serialNumber' => 'SN-DUP']);
})->throws(DuplicateItemSerialNumberException::class);

test('the service rejects a lot from another item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $otherItem = Item::factory()->create(['company_id' => $company->id]);
    $foreignLot = ItemLot::factory()->create(['item_id' => $otherItem->id, 'company_id' => $company->id]);

    createSerial($company->id, $item->id, [
        'createdBy' => $user->id,
        'lotId' => $foreignLot->id,
    ]);
})->throws(ItemSerialLotMismatchException::class);

test('a serial can hang from a lot of its own item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    $serial = createSerial($company->id, $item->id, [
        'createdBy' => $user->id,
        'lotId' => $lot->id,
    ]);

    expect($serial->lot_id)->toBe($lot->id);
});

test('creating a serial as sold stamps the exit date', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);

    $serial = createSerial($company->id, $item->id, [
        'createdBy' => $user->id,
        'status' => 'sold',
    ]);

    expect($serial->sold_at)->not->toBeNull();
});

test('a rejected serial is never written', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'inventoried']);

    try {
        createSerial($company->id, $item->id, ['createdBy' => $user->id]);
    } catch (ItemSerialNotTrackableException) {
        // Se comprueba la tabla, no la excepción.
    }

    expect(ItemSerial::count())->toBe(0);
});

test('there is no route to create a serial from a screen', function () {
    expect(app('router')->getRoutes()->getByName('item-serials.create'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('item-serials.store'))->toBeNull();
});
