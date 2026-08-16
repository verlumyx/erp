<?php

declare(strict_types=1);

use App\Modules\Item\Commands\UpdateStatusItemCommand;
use App\Modules\Item\Exceptions\ItemNotFoundException;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Item\Services\ItemUpdateStatusService;

uses(Tests\TestCase::class);

test('it changes the status of the item', function () {
    $command = new UpdateStatusItemCommand(status: 'inactive');
    $model = new Item(['id' => 'item-uuid', 'status' => 'active']);

    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('findById')->with('item-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('item-uuid', null)
        ->andReturn(new Item(['id' => 'item-uuid', 'status' => 'inactive']));

    $service = new ItemUpdateStatusService($repository);

    expect($service->execute('item-uuid', $command)->status)->toBe('inactive');
});

test('it throws when the item to deactivate does not exist', function () {
    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ItemUpdateStatusService($repository);

    $service->execute('missing', new UpdateStatusItemCommand(status: 'inactive'));
})->throws(ItemNotFoundException::class);
