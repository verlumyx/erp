<?php

declare(strict_types=1);

use App\Modules\Item\Commands\UpdateItemCommand;
use App\Modules\Item\Exceptions\ItemNotFoundException;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Item\Services\ItemUpdateService;

uses(Tests\TestCase::class);

test('it updates the item and returns it refreshed', function () {
    $command = new UpdateItemCommand(sku: 'SKU-002', name: 'Martillo grande');
    $model = new Item(['id' => 'item-uuid', 'name' => 'Martillo']);

    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('findById')->with('item-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('item-uuid', 'company-uuid')
        ->andReturn(new Item(['id' => 'item-uuid', 'name' => 'Martillo grande']));

    $service = new ItemUpdateService($repository);

    expect($service->execute('item-uuid', $command, 'company-uuid')->name)->toBe('Martillo grande');
});

test('it throws when updating an item that does not exist', function () {
    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ItemUpdateService($repository);

    $service->execute('missing', new UpdateItemCommand(sku: 'SKU', name: 'X'));
})->throws(ItemNotFoundException::class);
