<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Commands\CreateItemLotCommand;
use App\Modules\ItemLot\Exceptions\DuplicateItemLotNumberException;
use App\Modules\ItemLot\Exceptions\InvalidItemLotDatesException;
use App\Modules\ItemLot\Exceptions\ItemLotNotTrackableException;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Services\ItemLotCreateService;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Support\Str;

/**
 * El lote no se crea desde pantalla: nace en el documento que recibe la
 * mercancía. Estos tests ejercen el servicio que ese documento llamará, porque
 * es donde viven las invariantes ahora que no hay `FormRequest` delante.
 */
function createLot(string $companyId, string $itemId, array $overrides = []): ItemLot
{
    return app(ItemLotCreateService::class)->execute(new CreateItemLotCommand(
        id: $overrides['id'] ?? (string) Str::uuid7(),
        companyId: $companyId,
        itemId: $itemId,
        lotNumber: $overrides['lotNumber'] ?? 'L-2026-001',
        createdBy: $overrides['createdBy'],
        manufacturedAt: $overrides['manufacturedAt'] ?? null,
        expiresAt: $overrides['expiresAt'] ?? null,
        supplierId: $overrides['supplierId'] ?? null,
    ));
}

test('the service creates a lot for an inventoried item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);

    $lot = createLot($company->id, $item->id, [
        'createdBy' => $user->id,
        'manufacturedAt' => '2026-01-10',
        'expiresAt' => '2027-01-10',
        'supplierId' => $supplier->id,
    ]);

    expect($lot->lot_number)->toBe('L-2026-001');
    expect($lot->manufactured_at->format('Y-m-d'))->toBe('2026-01-10');
    expect($lot->expires_at->format('Y-m-d'))->toBe('2027-01-10');
    expect($lot->supplier_id)->toBe($supplier->id);
    expect($lot->status)->toBe('active');
    expect($lot->company_id)->toBe($company->id);
    expect($lot->created_by)->toBe($user->id);
    expect($lot->code)->toBe('LOT000001');
});

test('the lot code is sequential per company', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);

    createLot($company->id, $item->id, ['createdBy' => $user->id, 'lotNumber' => 'L-1']);
    createLot($company->id, $item->id, ['createdBy' => $user->id, 'lotNumber' => 'L-2']);

    expect(ItemLot::orderBy('code')->pluck('code')->all())->toBe(['LOT000001', 'LOT000002']);
});

test('the service rejects a repeated lot number on the same item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    ItemLot::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'lot_number' => 'L-DUP',
    ]);

    createLot($company->id, $item->id, ['createdBy' => $user->id, 'lotNumber' => 'L-DUP']);
})->throws(DuplicateItemLotNumberException::class);

test('the same lot number is allowed on a different item', function () {
    [$user, $company] = createUserWithCompany();

    $first = Item::factory()->create(['company_id' => $company->id]);
    $second = Item::factory()->create(['company_id' => $company->id]);

    ItemLot::factory()->create([
        'item_id' => $first->id,
        'company_id' => $company->id,
        'lot_number' => 'L-SHARED',
    ]);

    $lot = createLot($company->id, $second->id, [
        'createdBy' => $user->id,
        'lotNumber' => 'L-SHARED',
    ]);

    expect($lot->item_id)->toBe($second->id);
});

test('the service rejects an item that does not affect stock', function () {
    [$user, $company] = createUserWithCompany();

    $service = Item::factory()->create(['company_id' => $company->id, 'type' => 'service']);

    createLot($company->id, $service->id, ['createdBy' => $user->id]);
})->throws(ItemLotNotTrackableException::class);

/**
 * El lote no es un tipo de artículo: lo admite cualquiera que mueva mercancía y
 * ninguno que no la mueva. Los casos salen del catálogo y no de una lista
 * escrita a mano, para que un tipo nuevo no estrene la regla a medias —que fue
 * exactamente lo que pasó con `kit`, que la pantalla rotulaba «Lotes» y era el
 * único con existencia al que el lote le estaba prohibido—.
 */
test('any item that moves stock can carry a lot', function (string $type) {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => $type]);

    $lot = createLot($company->id, $item->id, ['createdBy' => $user->id]);

    expect($lot->item_id)->toBe($item->id);
})->with(array_values(array_diff(Item::TYPES, Item::NON_STOCKED_TYPES)));

test('an item without stock never carries a lot', function (string $type) {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => $type]);

    createLot($company->id, $item->id, ['createdBy' => $user->id]);
})->with(Item::NON_STOCKED_TYPES)->throws(ItemLotNotTrackableException::class);

test('the service rejects an item from another company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $foreign = Item::factory()->create(['company_id' => $otherCompany->id]);

    createLot($company->id, $foreign->id, ['createdBy' => $user->id]);
})->throws(ItemLotNotTrackableException::class);

test('the service rejects an expiry date before the manufacturing date', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);

    createLot($company->id, $item->id, [
        'createdBy' => $user->id,
        'manufacturedAt' => '2026-05-10',
        'expiresAt' => '2026-01-10',
    ]);
})->throws(InvalidItemLotDatesException::class);

test('a rejected lot is never written', function () {
    [$user, $company] = createUserWithCompany();

    $service = Item::factory()->create(['company_id' => $company->id, 'type' => 'service']);

    try {
        createLot($company->id, $service->id, ['createdBy' => $user->id]);
    } catch (ItemLotNotTrackableException) {
        // Se comprueba la tabla, no la excepción.
    }

    expect(ItemLot::count())->toBe(0);
});

test('there is no route to create a lot from a screen', function () {
    expect(app('router')->getRoutes()->getByName('item-lots.create'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('item-lots.store'))->toBeNull();
});
