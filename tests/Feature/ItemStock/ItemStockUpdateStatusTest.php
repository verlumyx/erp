<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

/**
 * Saldo listo para probar el cambio de estado.
 *
 * @param  array<string, mixed>  $attributes
 */
function retirableStock(string $companyId, array $attributes = []): ItemStock
{
    $item = Item::factory()->create(['company_id' => $companyId]);
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

test('a balance at zero can be retired', function () {
    [$user, $company] = createUserWithCompany();

    $stock = retirableStock($company->id);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-stocks.update-status', ['company' => $company->id, 'id' => $stock->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('item-stocks.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();
    expect($stock->refresh()->status)->toBe('inactive');
});

test('a balance with quantity cannot be retired', function () {
    [$user, $company] = createUserWithCompany();

    $stock = retirableStock($company->id, ['quantity' => 5, 'available_quantity' => 5]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-stocks.update-status', ['company' => $company->id, 'id' => $stock->id]), [
            'status' => 'inactive',
        ])->assertSessionHasErrors('status');

    expect($stock->refresh()->status)->toBe('active');
});

test('a balance with a reservation cannot be retired', function () {
    [$user, $company] = createUserWithCompany();

    $stock = retirableStock($company->id, ['reserved_quantity' => 2]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-stocks.update-status', ['company' => $company->id, 'id' => $stock->id]), [
            'status' => 'inactive',
        ])->assertSessionHasErrors('status');
});

test('a balance with goods in transit cannot be retired', function () {
    [$user, $company] = createUserWithCompany();

    $stock = retirableStock($company->id, ['incoming_quantity' => 7]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-stocks.update-status', ['company' => $company->id, 'id' => $stock->id]), [
            'status' => 'inactive',
        ])->assertSessionHasErrors('status');
});

test('a retired balance can be brought back', function () {
    [$user, $company] = createUserWithCompany();

    $stock = retirableStock($company->id, ['status' => 'inactive']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-stocks.update-status', ['company' => $company->id, 'id' => $stock->id]), [
            'status' => 'active',
        ])->assertSessionHasNoErrors();

    expect($stock->refresh()->status)->toBe('active');
});
