<?php

declare(strict_types=1);

namespace App\Modules\Menu\Services;

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Repositories\Contracts\MenuRepositoryInterface;
use App\Modules\User\Models\User;

class GetActiveMenusService
{
    public function __construct(
        private readonly MenuRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ mainNavItems: array<mixed>, footerNavItems: array<mixed> }
     */
    public function execute(?User $user = null): array
    {
        $mainMenus = $this->repository->findActiveRootsBySection('main');
        $footerMenus = $this->repository->findActiveRootsBySection('footer');

        if ($user) {
            $mainMenus = $this->filterByPermissions($mainMenus, $user);
            $footerMenus = $this->filterByPermissions($footerMenus, $user);
        }

        return [
            'mainNavItems' => array_values(array_map(fn (Menu $menu) => $this->toArray($menu), $mainMenus)),
            'footerNavItems' => array_values(array_map(fn (Menu $menu) => $this->toArray($menu), $footerMenus)),
        ];
    }

    /**
     * @param  array<Menu>  $menus
     * @return array<Menu>
     */
    private function filterByPermissions(array $menus, User $user): array
    {
        return array_values(array_filter($menus, function (Menu $menu) use ($user) {
            if (! $menu->permission) {
                return true;
            }

            if ($menu->permission === 'system_owner') {
                return $user->is_system_owner;
            }

            return $user->hasPermission($menu->permission);
        }));
    }

    /** @return array<mixed> */
    private function toArray(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'title' => $menu->title,
            'icon' => $menu->icon,
            'url' => $menu->url,
            'permission' => $menu->permission,
            'children' => array_values($menu->children->map(fn (Menu $child) => $this->toArray($child))->all()),
        ];
    }
}
