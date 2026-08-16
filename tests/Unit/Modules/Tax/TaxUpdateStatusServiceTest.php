<?php

declare(strict_types=1);

use App\Modules\Tax\Commands\UpdateStatusTaxCommand;
use App\Modules\Tax\Exceptions\TaxNotFoundException;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\Tax\Services\TaxUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the status and returns the refreshed model', function () {
    $command = new UpdateStatusTaxCommand(status: 'inactive');
    $model = new Tax(['status' => 'active']);

    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('findById')->with('tax-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('tax-uuid', null)
        ->andReturn(new Tax(['status' => 'inactive']));

    $service = new TaxUpdateStatusService($repository);

    expect($service->execute('tax-uuid', $command)->status)->toBe('inactive');
});

test('it throws when changing the status of a missing tax', function () {
    $command = new UpdateStatusTaxCommand(status: 'inactive');

    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    (new TaxUpdateStatusService($repository))->execute('missing-uuid', $command);
})->throws(TaxNotFoundException::class);
