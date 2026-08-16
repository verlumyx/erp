<?php

declare(strict_types=1);

use App\Modules\Category\Commands\UpdateCategoryCommand;
use App\Modules\Category\Exceptions\CategoryNotFoundException;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Modules\Category\Services\CategoryUpdateService;

uses(Tests\TestCase::class);

test('it updates a category and returns the refreshed model', function () {
    $command = new UpdateCategoryCommand(name: 'Bebidas frías', description: null, order: 4);
    $model = new Category(['id' => 'category-uuid', 'name' => 'Bebidas']);

    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('findById')->with('category-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('category-uuid', 'company-uuid')
        ->andReturn(new Category(['id' => 'category-uuid', 'name' => 'Bebidas frías']));

    $service = new CategoryUpdateService($repository);

    expect($service->execute('category-uuid', $command, 'company-uuid')->name)->toBe('Bebidas frías');
});

test('it throws when updating a missing category', function () {
    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new CategoryUpdateService($repository);

    $service->execute('missing-uuid', new UpdateCategoryCommand(name: 'X'));
})->throws(CategoryNotFoundException::class);
