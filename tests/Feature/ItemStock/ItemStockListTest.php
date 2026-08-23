<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemStock\Exceptions\ItemStockNotFoundException;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * Crea un saldo dentro de la empresa dada, con su artículo, bodega y ubicación.
 *
 * @param  array<string, mixed>  $attributes
 */
function stockRowFor(string $companyId, string $itemName, array $attributes = []): ItemStock
{
    $item = Item::factory()->create(['company_id' => $companyId, 'name' => $itemName]);
    $warehouse = Warehouse::factory()->create(['company_id' => $companyId]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $companyId,
    ]);

    return ItemStock::factory()->create([
        'company_id' => $companyId,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'location_id' => $location->id,
        ...$attributes,
    ]);
}

test('the list only shows balances of the active company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    stockRowFor($company->id, 'Artículo propio');
    stockRowFor($otherCompany->id, 'Artículo ajeno');

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-stocks.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('item-stocks/index')
        ->has('stocks', 1)
        ->where('stocks.0.item_name', 'Artículo propio')
        ->where('meta.total', 1)
        ->has('warehouses')
    );
});

test('the list can hide balances that are already at zero', function () {
    [$user, $company] = createUserWithCompany();

    stockRowFor($company->id, 'Con saldo', ['quantity' => 12, 'available_quantity' => 12]);
    stockRowFor($company->id, 'En cero');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-stocks.index', ['company' => $company->id, 'with_stock' => 'yes']))
        ->assertInertia(fn ($page) => $page
            ->has('stocks', 1)
            ->where('stocks.0.item_name', 'Con saldo')
        );
});

test('the list can be searched by item name', function () {
    [$user, $company] = createUserWithCompany();

    stockRowFor($company->id, 'Taladro percutor');
    stockRowFor($company->id, 'Cemento gris');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-stocks.index', ['company' => $company->id, 'q' => 'Taladro']))
        ->assertInertia(fn ($page) => $page
            ->has('stocks', 1)
            ->where('stocks.0.item_name', 'Taladro percutor')
        );
});

test('a balance shows its item, warehouse and location', function () {
    [$user, $company] = createUserWithCompany();

    $stock = stockRowFor($company->id, 'Cemento gris', [
        'quantity' => 8,
        'reserved_quantity' => 3,
        'available_quantity' => 5,
        'average_cost' => 2.5,
        'total_value' => 20,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-stocks.show', ['company' => $company->id, 'id' => $stock->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('item-stocks/show')
            ->where('stock.item_name', 'Cemento gris')
            ->where('stock.quantity', 8)
            ->where('stock.reserved_quantity', 3)
            ->where('stock.available_quantity', 5)
            ->where('stock.total_value', 20)
            ->whereNot('stock.warehouse_name', null)
            ->whereNot('stock.location_name', null)
        );
});

test('showing a missing balance throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('item-stocks.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(ItemStockNotFoundException::class);

test('a balance from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $foreign = stockRowFor($otherCompany->id, 'Ajeno');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('item-stocks.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(ItemStockNotFoundException::class);

test('there is no route to create or edit a balance by hand', function () {
    expect(app('router')->getRoutes()->getByName('item-stocks.create'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('item-stocks.store'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('item-stocks.edit'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('item-stocks.update'))->toBeNull();
});
