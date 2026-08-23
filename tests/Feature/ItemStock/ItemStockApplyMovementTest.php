<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\ItemStock\Exceptions\InvalidStockLocationException;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Services\ItemStockApplyMovementService;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

/**
 * Monta un artículo con su bodega y su ubicación por defecto dentro de una
 * empresa recién creada.
 *
 * @return array{0: \App\Modules\Company\Models\Company, 1: Item, 2: Warehouse, 3: WarehouseLocation}
 */
function stockFixture(string $allowsNegative = 'no'): array
{
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);
    $warehouse = Warehouse::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'allows_negative_stock' => $allowsNegative,
    ]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    return [$company, $item, $warehouse, $location];
}

function applyMovement(ApplyItemStockMovementCommand $command): ItemStock
{
    return app(ItemStockApplyMovementService::class)->execute($command);
}

test('the first movement creates the balance and sets the average cost', function () {
    [$company, $item, $warehouse, $location] = stockFixture();

    $stock = applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: 10,
        unitCost: 5,
    ));

    expect((float) $stock->quantity)->toBe(10.0);
    expect((float) $stock->available_quantity)->toBe(10.0);
    expect((float) $stock->average_cost)->toBe(5.0);
    expect((float) $stock->total_value)->toBe(50.0);
    expect($stock->last_movement_at)->not->toBeNull();
    expect(ItemStock::count())->toBe(1);
});

test('a second entry recalculates the weighted average cost', function () {
    [$company, $item, $warehouse, $location] = stockFixture();

    $command = fn (float $quantity, float $cost): ApplyItemStockMovementCommand => new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: $quantity,
        unitCost: $cost,
    );

    applyMovement($command(10, 5));
    $stock = applyMovement($command(10, 7));

    /** (10 × 5 + 10 × 7) / 20 = 6 */
    expect((float) $stock->quantity)->toBe(20.0);
    expect((float) $stock->average_cost)->toBe(6.0);
    expect((float) $stock->total_value)->toBe(120.0);
});

test('an exit leaves the average cost untouched and reuses the same balance row', function () {
    [$company, $item, $warehouse, $location] = stockFixture();

    $base = fn (float $quantity, ?float $cost): ApplyItemStockMovementCommand => new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: $quantity,
        unitCost: $cost,
    );

    applyMovement($base(10, 5));
    $stock = applyMovement($base(-4, null));

    expect((float) $stock->quantity)->toBe(6.0);
    expect((float) $stock->average_cost)->toBe(5.0);
    expect((float) $stock->total_value)->toBe(30.0);
    expect(ItemStock::count())->toBe(1);
});

test('an exit that would leave the balance negative is rejected when the warehouse forbids it', function () {
    [$company, $item, $warehouse, $location] = stockFixture(allowsNegative: 'no');

    applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: 3,
        unitCost: 1,
    ));

    applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: -5,
    ));
})->throws(InsufficientStockException::class);

test('the rejected exit leaves the balance untouched', function () {
    [$company, $item, $warehouse, $location] = stockFixture(allowsNegative: 'no');

    applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: 3,
        unitCost: 1,
    ));

    try {
        applyMovement(new ApplyItemStockMovementCommand(
            companyId: $company->id,
            itemId: $item->id,
            warehouseId: $warehouse->id,
            locationId: $location->id,
            quantityDelta: -5,
        ));
    } catch (InsufficientStockException) {
        // Se comprueba el saldo, no la excepción.
    }

    expect((float) ItemStock::first()->quantity)->toBe(3.0);
});

test('a warehouse that allows negative stock accepts the exit', function () {
    [$company, $item, $warehouse, $location] = stockFixture(allowsNegative: 'yes');

    $stock = applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: -5,
    ));

    expect((float) $stock->quantity)->toBe(-5.0);
    expect((float) $stock->available_quantity)->toBe(-5.0);
});

test('a location of another warehouse is rejected', function () {
    [$company, $item, $warehouse] = stockFixture();

    $otherWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $foreignLocation = WarehouseLocation::factory()->create([
        'warehouse_id' => $otherWarehouse->id,
        'company_id' => $company->id,
    ]);

    applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $foreignLocation->id,
        quantityDelta: 1,
    ));
})->throws(InvalidStockLocationException::class);

test('the reservation lowers what is available without touching the physical quantity', function () {
    [$company, $item, $warehouse, $location] = stockFixture();

    applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: 10,
        unitCost: 2,
    ));

    $stock = applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        reservedDelta: 4,
    ));

    expect((float) $stock->quantity)->toBe(10.0);
    expect((float) $stock->reserved_quantity)->toBe(4.0);
    expect((float) $stock->available_quantity)->toBe(6.0);
});

test('what is in transit is tracked apart from the physical quantity', function () {
    [$company, $item, $warehouse, $location] = stockFixture();

    $stock = applyMovement(new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        incomingDelta: 25,
    ));

    expect((float) $stock->incoming_quantity)->toBe(25.0);
    expect((float) $stock->quantity)->toBe(0.0);
});

test('each lot keeps its own balance row', function () {
    [$company, $item, $warehouse, $location] = stockFixture();

    $first = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);
    $second = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    $movement = fn (?string $lotId, float $quantity): ApplyItemStockMovementCommand => new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: $quantity,
        lotId: $lotId,
    );

    applyMovement($movement($first->id, 4));
    applyMovement($movement($second->id, 6));
    applyMovement($movement(null, 1));

    expect(ItemStock::count())->toBe(3);
    expect((float) ItemStock::where('lot_id', $first->id)->value('quantity'))->toBe(4.0);
    expect((float) ItemStock::where('lot_id', $second->id)->value('quantity'))->toBe(6.0);
    expect((float) ItemStock::whereNull('lot_id')->value('quantity'))->toBe(1.0);
});

test('two movements on the same lot reuse the same balance row', function () {
    [$company, $item, $warehouse, $location] = stockFixture();

    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    $movement = fn (float $quantity): ApplyItemStockMovementCommand => new ApplyItemStockMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        quantityDelta: $quantity,
        lotId: $lot->id,
    );

    applyMovement($movement(4));
    applyMovement($movement(3));

    expect(ItemStock::count())->toBe(1);
    expect((float) ItemStock::first()->quantity)->toBe(7.0);
});
