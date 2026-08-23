<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Exceptions\InvalidMovementQuantityException;
use App\Modules\InventoryMovement\Exceptions\InvalidMovementTypeException;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Support\Str;

test('an entry writes the movement and loads the stock in one go', function () {
    [$company, $item, $warehouse, $location, $user] = kardexScenario();

    $movement = registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 5,
        'createdBy' => $user->id,
    ]);

    expect($movement->code)->toBe('MOV000001');
    expect($movement->type)->toBe('in');
    expect((float) $movement->quantity)->toBe(10.0);
    expect((float) $movement->unit_cost)->toBe(5.0);
    expect((float) $movement->total_cost)->toBe(50.0);
    expect($movement->status)->toBe('active');
    expect($movement->created_by)->toBe($user->id);

    expect((float) ItemStock::first()->quantity)->toBe(10.0);
    expect((float) ItemStock::first()->average_cost)->toBe(5.0);
});

test('the movement freezes the balance the item has in that warehouse', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);
    $second = registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 7]);

    /** (10 × 5 + 10 × 7) / 20 = 6 */
    expect((float) $second->balance_quantity)->toBe(20.0);
    expect((float) $second->balance_cost)->toBe(6.0);
    expect((float) $second->balance_value)->toBe(120.0);
});

test('the frozen balance of the first movement is not rewritten by the second', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $first = registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 7]);

    expect((float) $first->fresh()->balance_quantity)->toBe(10.0);
    expect((float) $first->fresh()->balance_value)->toBe(50.0);
});

test('an exit discharges the stock and is valued at the current average cost', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $exit = registerInventoryMovement($company, $item, $warehouse, $location, [
        'type' => 'out',
        'originType' => 'sales_invoice',
        'quantity' => 4,
    ]);

    expect((float) $exit->quantity)->toBe(4.0);
    expect((float) $exit->unit_cost)->toBe(5.0);
    expect((float) $exit->total_cost)->toBe(20.0);
    expect((float) $exit->balance_quantity)->toBe(6.0);
    expect((float) $exit->balance_value)->toBe(30.0);
    expect((float) ItemStock::first()->quantity)->toBe(6.0);
});

test('the balance adds up every location of the warehouse', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $second = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);
    $movement = registerInventoryMovement($company, $item, $warehouse, $second, ['quantity' => 6, 'unitCost' => 5]);

    expect(ItemStock::count())->toBe(2);
    expect((float) $movement->balance_quantity)->toBe(16.0);
    expect((float) $movement->balance_value)->toBe(80.0);
});

test('the balance of one warehouse ignores what another warehouse holds', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $other = Warehouse::factory()->create(['company_id' => $company->id]);
    $otherLocation = WarehouseLocation::factory()->create([
        'warehouse_id' => $other->id,
        'company_id' => $company->id,
    ]);

    registerInventoryMovement($company, $item, $other, $otherLocation, ['quantity' => 99, 'unitCost' => 5]);
    $movement = registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 4, 'unitCost' => 5]);

    expect((float) $movement->balance_quantity)->toBe(4.0);
});

test('an exit the warehouse cannot cover writes no movement at all', function () {
    [$company, $item, $warehouse, $location] = kardexScenario(allowsNegative: 'no');

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 3, 'unitCost' => 1]);

    try {
        registerInventoryMovement($company, $item, $warehouse, $location, ['type' => 'out', 'quantity' => 5]);
    } catch (InsufficientStockException) {
        // Se comprueba que la transacción no dejó rastro, no la excepción.
    }

    expect(InventoryMovement::count())->toBe(1);
    expect((float) ItemStock::first()->quantity)->toBe(3.0);
});

test('the lot travels from the movement down to its own balance row', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    $movement = registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 8,
        'unitCost' => 2,
        'lotId' => $lot->id,
    ]);

    expect($movement->lot_id)->toBe($lot->id);
    expect((float) ItemStock::where('lot_id', $lot->id)->value('quantity'))->toBe(8.0);
});

test('every movement carries the document that originated it', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $originId = (string) Str::uuid7();
    $lineId = (string) Str::uuid7();

    $movement = registerInventoryMovement($company, $item, $warehouse, $location, [
        'originType' => 'purchase_invoice',
        'originId' => $originId,
        'originLineId' => $lineId,
    ]);

    expect($movement->origin_type)->toBe('purchase_invoice');
    expect($movement->origin_id)->toBe($originId);
    expect($movement->origin_line_id)->toBe($lineId);
});

test('the sequential code runs per company', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();
    [$otherCompany, $otherItem, $otherWarehouse, $otherLocation] = kardexScenario();

    registerInventoryMovement($company, $item, $warehouse, $location);
    $second = registerInventoryMovement($company, $item, $warehouse, $location);
    $foreign = registerInventoryMovement($otherCompany, $otherItem, $otherWarehouse, $otherLocation);

    expect($second->code)->toBe('MOV000002');
    expect($foreign->code)->toBe('MOV000001');
});

test('a type outside the kardex is rejected', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['type' => 'sale']);
})->throws(InvalidMovementTypeException::class);

test('a quantity of zero or less is rejected', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 0]);
})->throws(InvalidMovementQuantityException::class);

test('a service does not reach the kardex', function () {
    [$company, , $warehouse, $location] = kardexScenario();

    $service = Item::factory()->create(['company_id' => $company->id, 'type' => 'service']);

    registerInventoryMovement($company, $service, $warehouse, $location);
})->throws(NonInventoriedItemException::class);

test('a non inventoried item does not reach the kardex either', function () {
    [$company, , $warehouse, $location] = kardexScenario();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'non_inventoried']);

    registerInventoryMovement($company, $item, $warehouse, $location);
})->throws(NonInventoriedItemException::class);

test('a transfer moves the stock between its two warehouses', function () {
    [$company, $item, $origin, $originLocation] = kardexScenario();

    $destination = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'transit']);
    $destinationLocation = WarehouseLocation::factory()->create([
        'warehouse_id' => $destination->id,
        'company_id' => $company->id,
    ]);

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 3]);

    $out = registerInventoryMovement($company, $item, $origin, $originLocation, [
        'type' => 'transfer_out',
        'originType' => 'transfer',
        'quantity' => 4,
    ]);

    $in = registerInventoryMovement($company, $item, $destination, $destinationLocation, [
        'type' => 'transfer_in',
        'originType' => 'transfer',
        'quantity' => 4,
        'unitCost' => 3,
    ]);

    expect((float) $out->balance_quantity)->toBe(6.0);
    expect((float) $in->balance_quantity)->toBe(4.0);
});
