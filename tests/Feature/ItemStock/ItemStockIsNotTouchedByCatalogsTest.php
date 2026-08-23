<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Commands\CreateItemLotCommand;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Services\ItemLotCreateService;
use App\Modules\ItemSerial\Commands\CreateItemSerialCommand;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Services\ItemSerialCreateService;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * Lotes y series son catálogos de trazabilidad, no documentos de inventario.
 *
 * Registrar un lote o una serie identifica mercancía; no declara que haya
 * llegado. La existencia solo la mueven Ajustes, Entradas, Despachos y
 * Traslados, a través del kardex. Estos tests fijan esa frontera para que un
 * cambio futuro no la cruce sin que nadie se entere: el alta se ejerce por el
 * servicio, que es como la llamará la Entrada, y la edición por HTTP, que es lo
 * único que sigue expuesto en pantalla.
 */
test('creating a lot through the service does not move any stock', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);

    app(ItemLotCreateService::class)->execute(new CreateItemLotCommand(
        id: (string) Str::uuid7(),
        companyId: $company->id,
        itemId: $item->id,
        lotNumber: 'L-SIN-SALDO',
        createdBy: $user->id,
    ));

    expect(ItemLot::count())->toBe(1);
    expect(ItemStock::count())->toBe(0);
});

test('creating a serial through the service does not move any stock', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    app(ItemSerialCreateService::class)->execute(new CreateItemSerialCommand(
        id: (string) Str::uuid7(),
        companyId: $company->id,
        itemId: $item->id,
        serialNumber: 'SN-SIN-SALDO',
        createdBy: $user->id,
        warehouseId: $warehouse->id,
    ));

    expect(ItemSerial::count())->toBe(1);
    expect(ItemStock::count())->toBe(0);
});

test('editing a lot does not move any stock', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update', ['company' => $company->id, 'id' => $lot->id]), [
            'lot_number' => 'L-EDITADO',
            'expires_at' => '2027-01-01',
        ])->assertSessionHasNoErrors();

    expect(ItemStock::count())->toBe(0);
});

test('moving a serial to another warehouse does not move any stock', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $serial = ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update', ['company' => $company->id, 'id' => $serial->id]), [
            'serial_number' => $serial->serial_number,
            'warehouse_id' => $warehouse->id,
        ])->assertSessionHasNoErrors();

    expect($serial->refresh()->warehouse_id)->toBe($warehouse->id);
    expect(ItemStock::count())->toBe(0);
});

test('selling a serial does not move any stock', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $serial = ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update-status', ['company' => $company->id, 'id' => $serial->id]), [
            'status' => 'sold',
        ])->assertSessionHasNoErrors();

    expect($serial->refresh()->status)->toBe('sold');
    expect(ItemStock::count())->toBe(0);
});
