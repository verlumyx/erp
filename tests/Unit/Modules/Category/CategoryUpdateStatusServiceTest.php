<?php

declare(strict_types=1);

use App\Modules\Category\Commands\UpdateStatusCategoryCommand;
use App\Modules\Category\Exceptions\CategoryNotFoundException;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Modules\Category\Services\CategoryUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the category status', function () {
    $command = new UpdateStatusCategoryCommand(status: 'inactive');
    $model = new Category(['id' => 'category-uuid', 'status' => 'active']);

    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('findById')->with('category-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('category-uuid', 'company-uuid')
        ->andReturn(new Category(['id' => 'category-uuid', 'status' => 'inactive']));

    $service = new CategoryUpdateStatusService($repository);

    expect($service->execute('category-uuid', $command, 'company-uuid')->status)->toBe('inactive');
});

test('it throws when changing the status of a missing category', function () {
    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new CategoryUpdateStatusService($repository);

    $service->execute('missing-uuid', new UpdateStatusCategoryCommand(status: 'inactive'));
})->throws(CategoryNotFoundException::class);
