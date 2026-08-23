<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\WriteInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\InvalidMovementQuantityException;
use App\Modules\InventoryMovement\Exceptions\InvalidMovementTypeException;
use App\Modules\InventoryMovement\Exceptions\InventoryMovementNotFoundException;
use App\Modules\InventoryMovement\Exceptions\MovementAlreadyReversedException;
use App\Modules\InventoryMovement\Exceptions\MovementNotReversibleException;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementFindService;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\InventoryMovement\Services\InventoryMovementSearchService;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\ItemStock\Services\ItemStockApplyMovementService;

uses(Tests\TestCase::class);

/**
 * Monta el registrador con todo simulado: el aplicador de existencia devuelve
 * el saldo dado y el repositorio de saldos, el consolidado de la bodega.
 *
 * @param  array{quantity: float, value: float}  $warehouseBalance
 */
function registerServiceWith(
    InventoryMovementRepositoryInterface $repository,
    ItemStock $stock,
    array $warehouseBalance,
    string $itemType = 'inventoried',
    ?Closure $onApply = null,
): InventoryMovementRegisterService {
    $applyService = Mockery::mock(ItemStockApplyMovementService::class);
    $expectation = $applyService->allows('execute')->andReturn($stock);

    if ($onApply !== null) {
        $expectation->withArgs(function (ApplyItemStockMovementCommand $command) use ($onApply): bool {
            $onApply($command);

            return true;
        });
    }

    $stockRepository = Mockery::mock(ItemStockRepositoryInterface::class);
    $stockRepository->allows('warehouseBalance')->andReturn($warehouseBalance);

    $itemRepository = Mockery::mock(ItemRepositoryInterface::class);
    $itemRepository->allows('findById')->andReturn(new Item(['type' => $itemType]));

    return new InventoryMovementRegisterService(
        $repository,
        $applyService,
        $stockRepository,
        $itemRepository,
    );
}

/**
 * @param  array<string, mixed>  $overrides
 */
function registerCommand(array $overrides = []): RegisterInventoryMovementCommand
{
    return new RegisterInventoryMovementCommand(
        companyId: 'company-uuid',
        itemId: 'item-uuid',
        warehouseId: 'warehouse-uuid',
        locationId: 'location-uuid',
        type: $overrides['type'] ?? 'in',
        originType: $overrides['originType'] ?? 'entry',
        originId: 'origin-uuid',
        quantity: $overrides['quantity'] ?? 10,
        unitCost: $overrides['unitCost'] ?? null,
    );
}

test('an entry values the movement at the cost it was bought for', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('register')
        ->withArgs(function (RegisterInventoryMovementCommand $command, WriteInventoryMovementCommand $write): bool {
            expect($write->unitCost)->toBe(7.0);
            expect($write->totalCost)->toBe(70.0);

            return true;
        })
        ->andReturn(new InventoryMovement);

    registerServiceWith(
        $repository,
        new ItemStock(['average_cost' => 6]),
        ['quantity' => 20.0, 'value' => 120.0],
    )->execute(registerCommand(['unitCost' => 7]));
});

test('an exit without an explicit cost takes the average of the balance', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('register')
        ->withArgs(function (RegisterInventoryMovementCommand $command, WriteInventoryMovementCommand $write): bool {
            expect($write->unitCost)->toBe(5.0);
            expect($write->totalCost)->toBe(20.0);

            return true;
        })
        ->andReturn(new InventoryMovement);

    registerServiceWith(
        $repository,
        new ItemStock(['average_cost' => 5]),
        ['quantity' => 6.0, 'value' => 30.0],
    )->execute(registerCommand(['type' => 'out', 'quantity' => 4]));
});

test('the frozen balance is the consolidated one of the warehouse', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('register')
        ->withArgs(function (RegisterInventoryMovementCommand $command, WriteInventoryMovementCommand $write): bool {
            /** 120 / 20 = 6 */
            expect($write->balanceQuantity)->toBe(20.0);
            expect($write->balanceCost)->toBe(6.0);
            expect($write->balanceValue)->toBe(120.0);

            return true;
        })
        ->andReturn(new InventoryMovement);

    registerServiceWith(
        $repository,
        new ItemStock(['average_cost' => 6]),
        ['quantity' => 20.0, 'value' => 120.0],
    )->execute(registerCommand(['unitCost' => 7]));
});

test('a balance at zero keeps the average of the row that was touched', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('register')
        ->withArgs(function (RegisterInventoryMovementCommand $command, WriteInventoryMovementCommand $write): bool {
            expect($write->balanceQuantity)->toBe(0.0);
            expect($write->balanceCost)->toBe(4.0);

            return true;
        })
        ->andReturn(new InventoryMovement);

    registerServiceWith(
        $repository,
        new ItemStock(['average_cost' => 4]),
        ['quantity' => 0.0, 'value' => 0.0],
    )->execute(registerCommand(['type' => 'out']));
});

test('the type decides the sign the balance receives', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->allows('register')->andReturn(new InventoryMovement);

    $deltas = [];

    foreach (['in', 'transfer_in', 'adjustment_in', 'out', 'transfer_out', 'adjustment_out'] as $type) {
        registerServiceWith(
            $repository,
            new ItemStock(['average_cost' => 1]),
            ['quantity' => 1.0, 'value' => 1.0],
            onApply: function (ApplyItemStockMovementCommand $command) use (&$deltas): void {
                $deltas[] = $command->quantityDelta;
            },
        )->execute(registerCommand(['type' => $type, 'quantity' => 3]));
    }

    expect($deltas)->toBe([3.0, 3.0, 3.0, -3.0, -3.0, -3.0]);
});

test('an exit does not carry a unit cost into the balance', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->allows('register')->andReturn(new InventoryMovement);

    registerServiceWith(
        $repository,
        new ItemStock(['average_cost' => 5]),
        ['quantity' => 1.0, 'value' => 5.0],
        onApply: function (ApplyItemStockMovementCommand $command): void {
            /** El promedio solo lo mueve una entrada: la salida no aporta costo. */
            expect($command->unitCost)->toBeNull();
        },
    )->execute(registerCommand(['type' => 'out', 'unitCost' => 9]));
});

test('a type outside the kardex never touches the balance', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('register')->never();

    registerServiceWith(
        $repository,
        new ItemStock,
        ['quantity' => 0.0, 'value' => 0.0],
    )->execute(registerCommand(['type' => 'sale']));
})->throws(InvalidMovementTypeException::class);

test('a quantity of zero never touches the balance', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('register')->never();

    registerServiceWith(
        $repository,
        new ItemStock,
        ['quantity' => 0.0, 'value' => 0.0],
    )->execute(registerCommand(['quantity' => 0]));
})->throws(InvalidMovementQuantityException::class);

test('an item that does not hold stock never touches the balance', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('register')->never();

    registerServiceWith(
        $repository,
        new ItemStock,
        ['quantity' => 0.0, 'value' => 0.0],
        itemType: 'service',
    )->execute(registerCommand());
})->throws(NonInventoriedItemException::class);

test('reversing a movement that does not exist throws', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $registerService = Mockery::mock(InventoryMovementRegisterService::class);
    $registerService->expects('execute')->never();

    (new InventoryMovementReverseService($repository, $registerService))
        ->execute(new ReverseInventoryMovementCommand(movementId: 'missing'));
})->throws(InventoryMovementNotFoundException::class);

test('an already reversed movement is not reversed twice', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('findById')->andReturn(new InventoryMovement(['status' => 'reversed']));
    $repository->expects('markReversed')->never();

    $registerService = Mockery::mock(InventoryMovementRegisterService::class);
    $registerService->expects('execute')->never();

    (new InventoryMovementReverseService($repository, $registerService))
        ->execute(new ReverseInventoryMovementCommand(movementId: 'movement-uuid'));
})->throws(MovementAlreadyReversedException::class);

test('a counter entry is not reversed in turn', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('findById')->andReturn(new InventoryMovement([
        'status' => 'active',
        'reversal_of_id' => 'original-uuid',
    ]));
    $repository->expects('markReversed')->never();

    $registerService = Mockery::mock(InventoryMovementRegisterService::class);
    $registerService->expects('execute')->never();

    (new InventoryMovementReverseService($repository, $registerService))
        ->execute(new ReverseInventoryMovementCommand(movementId: 'movement-uuid'));
})->throws(MovementNotReversibleException::class);

test('the counter entry flips the type and points back at the original', function () {
    $original = new InventoryMovement([
        'company_id' => 'company-uuid',
        'item_id' => 'item-uuid',
        'warehouse_id' => 'warehouse-uuid',
        'location_id' => 'location-uuid',
        'type' => 'in',
        'origin_type' => 'purchase_invoice',
        'origin_id' => 'origin-uuid',
        'quantity' => 10,
        'unit_cost' => 5,
        'status' => 'active',
    ]);
    $original->id = 'original-uuid';

    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('findById')->andReturn($original);
    $repository->expects('markReversed')->with($original);

    $registerService = Mockery::mock(InventoryMovementRegisterService::class);
    $registerService->expects('execute')
        ->withArgs(function (RegisterInventoryMovementCommand $command): bool {
            expect($command->type)->toBe('out');
            expect($command->quantity)->toBe(10.0);
            expect($command->unitCost)->toBe(5.0);
            expect($command->reversalOfId)->toBe('original-uuid');
            expect($command->originType)->toBe('purchase_invoice');

            return true;
        })
        ->andReturn(new InventoryMovement(['type' => 'out']));

    $reversal = (new InventoryMovementReverseService($repository, $registerService))
        ->execute(new ReverseInventoryMovementCommand(movementId: 'original-uuid'));

    expect($reversal->type)->toBe('out');
});

test('finding a missing movement throws', function () {
    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new InventoryMovementFindService($repository))->execute('missing');
})->throws(InventoryMovementNotFoundException::class);

test('the kardex search service delegates to the repository', function () {
    $command = new SearchInventoryMovementCommand(filters: ['direction' => 'out']);
    $expected = ['data' => [], 'total' => 0];

    $repository = Mockery::mock(InventoryMovementRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    expect((new InventoryMovementSearchService($repository))->execute($command))->toBe($expected);
});
