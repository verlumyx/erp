<?php

declare(strict_types=1);

use App\Modules\Category\Commands\SearchCategoryCommand;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Modules\Category\Services\CategorySearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchCategoryCommand(filters: ['name' => 'bebidas'], limit: 10, offset: 0);
    $expected = ['data' => [new Category(['name' => 'Bebidas'])], 'total' => 1];

    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new CategorySearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
