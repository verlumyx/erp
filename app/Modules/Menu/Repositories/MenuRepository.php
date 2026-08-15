<?php

declare(strict_types=1);

namespace App\Modules\Menu\Repositories;

use App\Modules\Menu\Commands\SearchMenuCommand;
use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Repositories\Contracts\MenuRepositoryInterface;

class MenuRepository extends MenuFilters implements MenuRepositoryInterface
{
    public function findById(string $id): ?Menu
    {
        return Menu::query()->find($id);
    }

    /** @return Menu[] */
    public function findActiveRootsBySection(string $section): array
    {
        return Menu::query()
            ->where('is_active', true)
            ->where('section', $section)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('order');
            }])
            ->get()
            ->all();
    }

    /**
     * @return array{ data: Menu[], total: int }
     */
    public function search(SearchMenuCommand $command): array
    {
        $query = Menu::query()->where('is_active', true);

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query
            ->orderBy('order')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }
}
