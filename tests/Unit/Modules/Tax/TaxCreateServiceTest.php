<?php

declare(strict_types=1);

use App\Modules\Tax\Commands\CreateTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\Tax\Services\TaxCreateService;

uses(Tests\TestCase::class);

test('it creates a tax and returns the persisted model', function () {
    $command = new CreateTaxCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'IVA 15%',
        percentage: '15',
        hasWithholding: 'no',
        withholdingPercentage: '0',
        createdBy: 'user-uuid',
        description: 'Impuesto al valor agregado',
    );

    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Tax(['id' => $command->id, 'name' => 'IVA 15%', 'percentage' => '15']));

    $service = new TaxCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Tax::class);
    expect($result->name)->toBe('IVA 15%');
});
