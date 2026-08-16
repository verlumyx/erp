<?php

declare(strict_types=1);

use App\Modules\Category\Commands\CreateCategoryCommand;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Modules\Category\Services\CategoryCreateService;

uses(Tests\TestCase::class);

test('it creates a category and returns the persisted model', function () {
    $command = new CreateCategoryCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Bebidas',
        createdBy: 'user-uuid',
        description: 'Refrescos y jugos',
        order: 2,
    );

    $repository = Mockery::mock(CategoryRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Category(['id' => $command->id, 'name' => 'Bebidas']));

    $service = new CategoryCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Category::class);
    expect($result->name)->toBe('Bebidas');
});
