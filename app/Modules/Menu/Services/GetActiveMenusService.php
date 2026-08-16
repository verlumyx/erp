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
        return [
            'mainNavItems' => $this->buildSection('main', $user),
            'footerNavItems' => $this->buildSection('footer', $user),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSection(string $section, ?User $user): array
    {
        $menus = $this->repository->findActiveRootsBySection($section);

        return $this->buildNodes($menus, $user);
    }

    /**
     * @param  iterable<Menu>  $menus
     * @return array<int, array<string, mixed>>
     */
    private function buildNodes(iterable $menus, ?User $user): array
    {
        $nodes = [];

        foreach ($menus as $menu) {
            $node = $this->toArray($menu, $user);

            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * Build a menu node, or return null when it must be hidden: either the user
     * lacks its permission, or it is an empty group — a node with no URL of its
     * own whose children were all filtered out.
     *
     * @return array<string, mixed>|null
     */
    private function toArray(Menu $menu, ?User $user): ?array
    {
        if ($user !== null && ! $this->isVisibleTo($menu, $user)) {
            return null;
        }

        $children = $this->buildNodes($menu->children, $user);

        if ($children === [] && ($menu->url === null || $menu->url === '')) {
            return null;
        }

        return [
            'id' => $menu->id,
            'title' => $menu->title,
            'icon' => $menu->icon,
            'url' => $menu->url,
            'permission' => $menu->permission,
            'children' => $children,
        ];
    }

    private function isVisibleTo(Menu $menu, User $user): bool
    {
        if (! $menu->permission) {
            return true;
        }

        if ($menu->permission === 'system_owner') {
            return $user->is_system_owner;
        }

        return $user->hasPermission($menu->permission);
    }
}
