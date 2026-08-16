<?php

declare(strict_types=1);

use App\Modules\Tax\Commands\UpdateTaxCommand;
use App\Modules\Tax\Exceptions\TaxNotFoundException;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\Tax\Services\TaxUpdateService;

uses(Tests\TestCase::class);

test('it updates the tax and returns the refreshed model', function () {
    $command = new UpdateTaxCommand(
        name: 'IVA 16%',
        percentage: '16',
        hasWithholding: 'no',
        withholdingPercentage: '0',
    );
    $model = new Tax(['name' => 'IVA 15%', 'percentage' => '15']);

    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('findById')->with('tax-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('tax-uuid', null)
        ->andReturn(new Tax(['name' => 'IVA 16%', 'percentage' => '16']));

    $service = new TaxUpdateService($repository);

    expect($service->execute('tax-uuid', $command)->name)->toBe('IVA 16%');
});

test('it throws when updating a missing tax', function () {
    $command = new UpdateTaxCommand(
        name: 'IVA 16%',
        percentage: '16',
        hasWithholding: 'no',
        withholdingPercentage: '0',
    );

    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    (new TaxUpdateService($repository))->execute('missing-uuid', $command);
})->throws(TaxNotFoundException::class);
