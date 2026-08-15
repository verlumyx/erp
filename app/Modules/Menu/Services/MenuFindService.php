<?php

declare(strict_types=1);

namespace App\Modules\Menu\Services;

use App\Modules\Menu\Exceptions\MenuNotFoundException;
use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Repositories\Contracts\MenuRepositoryInterface;

class MenuFindService
{
    public function __construct(
        private readonly MenuRepositoryInterface $repository,
    ) {}

    public function execute(string $id): Menu
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new MenuNotFoundException;
        }

        return $model;
    }
}
