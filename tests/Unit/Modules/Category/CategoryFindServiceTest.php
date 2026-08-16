<?php

declare(strict_types=1);

use App\Modules\Category\Exceptions\CategoryNotFoundException;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Modules\Category\Services\CategoryFindService;

uses(Tests\TestCase::class);

test('it returns the category found by the repository', function () {
    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('findById')
        ->with('category-uuid', 'company-uuid')
        ->andReturn(new Category(['id' => 'category-uuid', 'name' => 'Bebidas']));

    $service = new CategoryFindService($repository);

    expect($service->execute('category-uuid', 'company-uuid')->name)->toBe('Bebidas');
});

test('it throws when the category does not exist', function () {
    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new CategoryFindService($repository);

    $service->execute('missing-uuid');
})->throws(CategoryNotFoundException::class);
