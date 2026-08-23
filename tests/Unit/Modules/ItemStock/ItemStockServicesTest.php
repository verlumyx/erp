<?php

declare(strict_types=1);

use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Commands\SearchItemStockCommand;
use App\Modules\ItemStock\Commands\UpdateStatusItemStockCommand;
use App\Modules\ItemStock\Commands\WriteItemStockBalanceCommand;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\ItemStock\Exceptions\InvalidStockLocationException;
use App\Modules\ItemStock\Exceptions\ItemStockNotFoundException;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\ItemStock\Services\ItemStockApplyMovementService;
use App\Modules\ItemStock\Services\ItemStockFindService;
use App\Modules\ItemStock\Services\ItemStockSearchService;
use App\Modules\ItemStock\Services\ItemStockUpdateStatusService;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;

uses(Tests\TestCase::class);

/**
 * Monta el servicio con los tres repositorios simulados.
 *
 * @param  array<string, mixed>  $warehouseAttributes
 */
function movementServiceWith(
    ItemStockRepositoryInterface $stockRepository,
    array $warehouseAttributes = [],
    bool $locationMatches = true,
): ItemStockApplyMovementService {
    $warehouse = new Warehouse(['allows_negative_stock' => 'no', ...$warehouseAttributes]);
    $warehouse->id = 'warehouse-uuid';

    $location = new WarehouseLocation([
        'warehouse_id' => $locationMatches ? 'warehouse-uuid' : 'other-warehouse-uuid',
    ]);
    $location->id = 'location-uuid';

    $warehouseRepository = Mockery::mock(WarehouseRepositoryInterface::class);
    $warehouseRepository->allows('findById')->andReturn($warehouse);

    $locationRepository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $locationRepository->allows('findById')->andReturn($location);

    return new ItemStockApplyMovementService($stockRepository, $warehouseRepository, $locationRepository);
}

function movementCommand(float $quantityDelta, ?float $unitCost = null): ApplyItemStockMovementCommand
{
    return new ApplyItemStockMovementCommand(
        companyId: 'company-uuid',
        itemId: 'item-uuid',
        warehouseId: 'warehouse-uuid',
        locationId: 'location-uuid',
        quantityDelta: $quantityDelta,
        unitCost: $unitCost,
    );
}

test('the movement writes the weighted average and the derived columns', function () {
    $stock = new ItemStock([
        'quantity' => 10,
        'reserved_quantity' => 2,
        'incoming_quantity' => 0,
        'average_cost' => 5,
    ]);

    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('lockBalance')->andReturn($stock);
    $repository->expects('writeBalance')
        ->withArgs(function (ItemStock $model, WriteItemStockBalanceCommand $balance): bool {
            /** (10 × 5 + 10 × 7) / 20 = 6 */
            expect($balance->quantity)->toBe(20.0);
            expect($balance->averageCost)->toBe(6.0);
            expect($balance->totalValue)->toBe(120.0);
            expect($balance->availableQuantity)->toBe(18.0);
            expect($balance->lastMovementAt)->not->toBeNull();

            return true;
        })
        ->andReturn($stock);

    movementServiceWith($repository)->execute(movementCommand(10, 7));
});

test('an exit keeps the average cost of the balance', function () {
    $stock = new ItemStock(['quantity' => 10, 'average_cost' => 4]);

    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('lockBalance')->andReturn($stock);
    $repository->expects('writeBalance')
        ->withArgs(function (ItemStock $model, WriteItemStockBalanceCommand $balance): bool {
            expect($balance->quantity)->toBe(6.0);
            expect($balance->averageCost)->toBe(4.0);
            expect($balance->totalValue)->toBe(24.0);

            return true;
        })
        ->andReturn($stock);

    movementServiceWith($repository)->execute(movementCommand(-4));
});

test('an entry without a unit cost does not move the average', function () {
    $stock = new ItemStock(['quantity' => 10, 'average_cost' => 4]);

    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('lockBalance')->andReturn($stock);
    $repository->expects('writeBalance')
        ->withArgs(function (ItemStock $model, WriteItemStockBalanceCommand $balance): bool {
            expect($balance->averageCost)->toBe(4.0);

            return true;
        })
        ->andReturn($stock);

    movementServiceWith($repository)->execute(movementCommand(5));
});

test('a negative balance does not drag its deficit into the average', function () {
    $stock = new ItemStock(['quantity' => -4, 'average_cost' => 9]);

    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('lockBalance')->andReturn($stock);
    $repository->expects('writeBalance')
        ->withArgs(function (ItemStock $model, WriteItemStockBalanceCommand $balance): bool {
            /** El saldo negativo cuenta como cero: el promedio es el de la entrada. */
            expect($balance->quantity)->toBe(6.0);
            expect($balance->averageCost)->toBe(3.0);

            return true;
        })
        ->andReturn($stock);

    movementServiceWith($repository, ['allows_negative_stock' => 'yes'])->execute(movementCommand(10, 3));
});

test('the exit is rejected when the warehouse forbids negative stock', function () {
    $stock = new ItemStock(['quantity' => 2, 'average_cost' => 1]);

    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('lockBalance')->andReturn($stock);
    $repository->expects('writeBalance')->never();

    movementServiceWith($repository)->execute(movementCommand(-5));
})->throws(InsufficientStockException::class);

test('a location that does not belong to the warehouse never reaches the balance', function () {
    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('lockBalance')->never();

    movementServiceWith($repository, locationMatches: false)->execute(movementCommand(1));
})->throws(InvalidStockLocationException::class);

test('finding a missing balance throws', function () {
    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new ItemStockFindService($repository))->execute('missing');
})->throws(ItemStockNotFoundException::class);

test('it retires a balance that exists', function () {
    $command = new UpdateStatusItemStockCommand(status: 'inactive');
    $model = new ItemStock(['status' => 'active']);

    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('findById')->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')->andReturn(new ItemStock(['status' => 'inactive']));

    expect((new ItemStockUpdateStatusService($repository))->execute('stock-uuid', $command)->status)->toBe('inactive');
});

test('the stock search service delegates to the repository', function () {
    $command = new SearchItemStockCommand(filters: ['with_stock' => 'yes']);
    $expected = ['data' => [], 'total' => 0];

    $repository = Mockery::mock(ItemStockRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    expect((new ItemStockSearchService($repository))->execute($command))->toBe($expected);
});
