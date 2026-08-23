<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemLot\Commands\CreateItemLotCommand;
use App\Modules\ItemLot\Commands\SearchItemLotCommand;
use App\Modules\ItemLot\Commands\UpdateItemLotCommand;
use App\Modules\ItemLot\Commands\UpdateStatusItemLotCommand;
use App\Modules\ItemLot\Exceptions\DuplicateItemLotNumberException;
use App\Modules\ItemLot\Exceptions\InvalidItemLotDatesException;
use App\Modules\ItemLot\Exceptions\ItemLotNotFoundException;
use App\Modules\ItemLot\Exceptions\ItemLotNotTrackableException;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;
use App\Modules\ItemLot\Services\ItemLotCreateService;
use App\Modules\ItemLot\Services\ItemLotFindService;
use App\Modules\ItemLot\Services\ItemLotSearchService;
use App\Modules\ItemLot\Services\ItemLotUpdateService;
use App\Modules\ItemLot\Services\ItemLotUpdateStatusService;

uses(Tests\TestCase::class);

test('it creates a lot once the invariants hold', function () {
    $command = new CreateItemLotCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        itemId: 'item-uuid',
        lotNumber: 'L-001',
        createdBy: 'user-uuid',
    );

    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('lotNumberExists')
        ->with('company-uuid', 'item-uuid', 'L-001')
        ->andReturn(false);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new ItemLot(['id' => $command->id, 'lot_number' => 'L-001']));

    $itemRepository = Mockery::mock(ItemRepositoryInterface::class);
    $itemRepository->expects('findById')
        ->with('item-uuid', 'company-uuid')
        ->andReturn(new Item(['type' => 'inventoried']));

    $result = (new ItemLotCreateService($repository, $itemRepository))->execute($command);

    expect($result)->toBeInstanceOf(ItemLot::class);
    expect($result->lot_number)->toBe('L-001');
});

test('it refuses to create a lot for an item that does not affect stock', function () {
    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('create')->never();

    $itemRepository = Mockery::mock(ItemRepositoryInterface::class);
    $itemRepository->expects('findById')->andReturn(new Item(['type' => 'service']));

    (new ItemLotCreateService($repository, $itemRepository))->execute(new CreateItemLotCommand(
        id: 'lot-uuid',
        companyId: 'company-uuid',
        itemId: 'item-uuid',
        lotNumber: 'L-001',
        createdBy: 'user-uuid',
    ));
})->throws(ItemLotNotTrackableException::class);

test('it refuses to repeat a lot number within the item', function () {
    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('lotNumberExists')->andReturn(true);
    $repository->expects('create')->never();

    $itemRepository = Mockery::mock(ItemRepositoryInterface::class);
    $itemRepository->expects('findById')->andReturn(new Item(['type' => 'inventoried']));

    (new ItemLotCreateService($repository, $itemRepository))->execute(new CreateItemLotCommand(
        id: 'lot-uuid',
        companyId: 'company-uuid',
        itemId: 'item-uuid',
        lotNumber: 'L-DUP',
        createdBy: 'user-uuid',
    ));
})->throws(DuplicateItemLotNumberException::class);

test('it refuses an expiry date before the manufacturing date', function () {
    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('lotNumberExists')->andReturn(false);
    $repository->expects('create')->never();

    $itemRepository = Mockery::mock(ItemRepositoryInterface::class);
    $itemRepository->expects('findById')->andReturn(new Item(['type' => 'inventoried']));

    (new ItemLotCreateService($repository, $itemRepository))->execute(new CreateItemLotCommand(
        id: 'lot-uuid',
        companyId: 'company-uuid',
        itemId: 'item-uuid',
        lotNumber: 'L-001',
        createdBy: 'user-uuid',
        manufacturedAt: '2026-05-10',
        expiresAt: '2026-01-10',
    ));
})->throws(InvalidItemLotDatesException::class);

test('it updates a lot that exists', function () {
    $command = new UpdateItemLotCommand(lotNumber: 'L-NEW');
    $model = new ItemLot(['id' => 'lot-uuid', 'lot_number' => 'L-OLD']);

    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('findById')->with('lot-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('lot-uuid', 'company-uuid')
        ->andReturn(new ItemLot(['id' => 'lot-uuid', 'lot_number' => 'L-NEW']));

    $result = (new ItemLotUpdateService($repository))->execute('lot-uuid', $command, 'company-uuid');

    expect($result->lot_number)->toBe('L-NEW');
});

test('updating a missing lot throws', function () {
    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new ItemLotUpdateService($repository))->execute('missing', new UpdateItemLotCommand(lotNumber: 'L'));
})->throws(ItemLotNotFoundException::class);

test('it blocks a lot that exists', function () {
    $command = new UpdateStatusItemLotCommand(status: 'blocked');
    $model = new ItemLot(['id' => 'lot-uuid', 'status' => 'active']);

    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('findById')->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')->andReturn(new ItemLot(['id' => 'lot-uuid', 'status' => 'blocked']));

    $result = (new ItemLotUpdateStatusService($repository))->execute('lot-uuid', $command);

    expect($result->status)->toBe('blocked');
});

test('finding a missing lot throws', function () {
    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new ItemLotFindService($repository))->execute('missing');
})->throws(ItemLotNotFoundException::class);

test('the search service delegates to the repository', function () {
    $command = new SearchItemLotCommand(filters: ['status' => 'active']);
    $expected = ['data' => [], 'total' => 0];

    $repository = Mockery::mock(ItemLotRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    expect((new ItemLotSearchService($repository))->execute($command))->toBe($expected);
});
