<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;
use App\Modules\ItemSerial\Commands\CreateItemSerialCommand;
use App\Modules\ItemSerial\Commands\SearchItemSerialCommand;
use App\Modules\ItemSerial\Commands\UpdateItemSerialCommand;
use App\Modules\ItemSerial\Commands\UpdateStatusItemSerialCommand;
use App\Modules\ItemSerial\Exceptions\DuplicateItemSerialNumberException;
use App\Modules\ItemSerial\Exceptions\ItemSerialLotMismatchException;
use App\Modules\ItemSerial\Exceptions\ItemSerialNotFoundException;
use App\Modules\ItemSerial\Exceptions\ItemSerialNotTrackableException;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;
use App\Modules\ItemSerial\Services\ItemSerialCreateService;
use App\Modules\ItemSerial\Services\ItemSerialFindService;
use App\Modules\ItemSerial\Services\ItemSerialSearchService;
use App\Modules\ItemSerial\Services\ItemSerialUpdateService;
use App\Modules\ItemSerial\Services\ItemSerialUpdateStatusService;

uses(Tests\TestCase::class);

/**
 * Monta el servicio de alta con los tres repositorios simulados.
 */
function serialCreateServiceWith(
    ItemSerialRepositoryInterface $repository,
    string $itemType = 'serialized',
    ?ItemLot $lot = null,
): ItemSerialCreateService {
    $itemRepository = Mockery::mock(ItemRepositoryInterface::class);
    $itemRepository->allows('findById')->andReturn(new Item(['type' => $itemType]));

    $lotRepository = Mockery::mock(ItemLotRepositoryInterface::class);
    $lotRepository->allows('findById')->andReturn($lot);

    return new ItemSerialCreateService($repository, $itemRepository, $lotRepository);
}

function serialCreateCommand(?string $lotId = null): CreateItemSerialCommand
{
    return new CreateItemSerialCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        itemId: 'item-uuid',
        serialNumber: 'SN-001',
        createdBy: 'user-uuid',
        lotId: $lotId,
    );
}

test('it creates a serial once the invariants hold', function () {
    $command = serialCreateCommand();

    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('serialNumberExists')
        ->with('company-uuid', 'item-uuid', 'SN-001')
        ->andReturn(false);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new ItemSerial(['id' => $command->id, 'serial_number' => 'SN-001']));

    $result = serialCreateServiceWith($repository)->execute($command);

    expect($result)->toBeInstanceOf(ItemSerial::class);
    expect($result->serial_number)->toBe('SN-001');
});

test('it refuses to create a serial for an item that is not serialized', function () {
    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('create')->never();

    serialCreateServiceWith($repository, itemType: 'inventoried')->execute(serialCreateCommand());
})->throws(ItemSerialNotTrackableException::class);

test('it refuses to repeat a serial number within the item', function () {
    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('serialNumberExists')->andReturn(true);
    $repository->expects('create')->never();

    serialCreateServiceWith($repository)->execute(serialCreateCommand());
})->throws(DuplicateItemSerialNumberException::class);

test('it refuses a lot that belongs to another item', function () {
    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('serialNumberExists')->andReturn(false);
    $repository->expects('create')->never();

    $foreignLot = new ItemLot(['item_id' => 'another-item-uuid']);

    serialCreateServiceWith($repository, lot: $foreignLot)->execute(serialCreateCommand('lot-uuid'));
})->throws(ItemSerialLotMismatchException::class);

test('it updates a serial that exists', function () {
    $command = new UpdateItemSerialCommand(serialNumber: 'SN-NEW');
    $model = new ItemSerial(['id' => 'serial-uuid', 'serial_number' => 'SN-OLD']);

    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('findById')->with('serial-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('serial-uuid', 'company-uuid')
        ->andReturn(new ItemSerial(['id' => 'serial-uuid', 'serial_number' => 'SN-NEW']));

    $result = (new ItemSerialUpdateService($repository))->execute('serial-uuid', $command, 'company-uuid');

    expect($result->serial_number)->toBe('SN-NEW');
});

test('updating a missing serial throws', function () {
    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new ItemSerialUpdateService($repository))->execute('missing', new UpdateItemSerialCommand(serialNumber: 'SN'));
})->throws(ItemSerialNotFoundException::class);

test('it changes the status of a serial that exists', function () {
    $command = new UpdateStatusItemSerialCommand(status: 'sold');
    $model = new ItemSerial(['id' => 'serial-uuid', 'status' => 'available']);

    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('findById')->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')->andReturn(new ItemSerial(['id' => 'serial-uuid', 'status' => 'sold']));

    expect((new ItemSerialUpdateStatusService($repository))->execute('serial-uuid', $command)->status)->toBe('sold');
});

test('finding a missing serial throws', function () {
    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new ItemSerialFindService($repository))->execute('missing');
})->throws(ItemSerialNotFoundException::class);

test('the serial search service delegates to the repository', function () {
    $command = new SearchItemSerialCommand(filters: ['status' => 'available']);
    $expected = ['data' => [], 'total' => 0];

    $repository = Mockery::mock(ItemSerialRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    expect((new ItemSerialSearchService($repository))->execute($command))->toBe($expected);
});
