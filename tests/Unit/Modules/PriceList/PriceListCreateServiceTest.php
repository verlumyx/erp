<?php

declare(strict_types=1);

use App\Modules\PriceList\Commands\CreatePriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\PriceList\Services\PriceListCreateService;

uses(Tests\TestCase::class);

test('it creates a price list and returns the persisted model', function () {
    $command = new CreatePriceListCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Mayorista',
        createdBy: 'user-uuid',
        description: 'Precios para revendedores',
    );

    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new PriceList(['id' => $command->id, 'name' => 'Mayorista']));

    $service = new PriceListCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(PriceList::class);
    expect($result->name)->toBe('Mayorista');
});
