<?php

declare(strict_types=1);

namespace App\Modules\Menu\Repositories\Contracts;

use App\Modules\Menu\Commands\SearchMenuCommand;
use App\Modules\Menu\Models\Menu;

interface MenuRepositoryInterface
{
    public function findById(string $id): ?Menu;

    /** @return array{ data: Menu[], total: int } */
    public function search(SearchMenuCommand $command): array;

    /** @return Menu[] */
    public function findActiveRootsBySection(string $section): array;
}
