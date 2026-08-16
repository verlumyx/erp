<?php

declare(strict_types=1);

use App\Modules\Item\Commands\CreateItemCommand;
use App\Modules\Item\Commands\ItemUnitData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Item\Services\ItemCreateService;

uses(Tests\TestCase::class);

test('it creates an item and returns the persisted model', function () {
    $command = new CreateItemCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        sku: 'SKU-001',
        name: 'Martillo',
        createdBy: 'user-uuid',
        units: [new ItemUnitData(
            measurementUnitId: 'unit-uuid',
            isBase: 'yes',
            conversionFactor: '1',
        )],
    );

    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Item(['id' => $command->id, 'name' => 'Martillo']));

    $service = new ItemCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Item::class);
    expect($result->name)->toBe('Martillo');
});
