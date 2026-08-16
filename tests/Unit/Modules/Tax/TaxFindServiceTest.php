<?php

declare(strict_types=1);

use App\Modules\Tax\Exceptions\TaxNotFoundException;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\Tax\Services\TaxFindService;

uses(Tests\TestCase::class);

test('it returns the tax found by the repository', function () {
    $tax = new Tax(['name' => 'IVA 15%']);

    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('findById')->with('tax-uuid', null)->andReturn($tax);

    $service = new TaxFindService($repository);

    expect($service->execute('tax-uuid'))->toBe($tax);
});

test('it throws when the tax does not exist', function () {
    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    (new TaxFindService($repository))->execute('missing-uuid');
})->throws(TaxNotFoundException::class);
