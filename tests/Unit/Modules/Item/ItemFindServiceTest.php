<?php

declare(strict_types=1);

use App\Modules\Item\Exceptions\ItemNotFoundException;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Item\Services\ItemFindService;

uses(Tests\TestCase::class);

test('it returns the item when it exists', function () {
    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('findById')
        ->with('item-uuid', 'company-uuid')
        ->andReturn(new Item(['id' => 'item-uuid', 'name' => 'Martillo']));

    $service = new ItemFindService($repository);

    expect($service->execute('item-uuid', 'company-uuid')->name)->toBe('Martillo');
});

test('it throws when the item does not exist', function () {
    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ItemFindService($repository);

    $service->execute('missing');
})->throws(ItemNotFoundException::class);
