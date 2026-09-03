<?php

declare(strict_types=1);

namespace App\Modules\Menu\Services;

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Repositories\Contracts\MenuRepositoryInterface;
use App\Modules\Shared\Repositories\Contracts\CompanyDisabledMenuRepositoryInterface;
use App\Modules\User\Models\User;

class GetActiveMenusService
{
    /** Permiso reservado: el menú lo ve solo el dueño del sistema y ninguna empresa puede esconderlo. */
    private const SYSTEM_OWNER_PERMISSION = 'system_owner';

    public function __construct(
        private readonly MenuRepositoryInterface $repository,
        private readonly CompanyDisabledMenuRepositoryInterface $disabledMenus,
    ) {}

    /**
     * Sin usuario no se filtra por permisos; sin empresa no se esconde nada.
     * Con ambos en null se obtiene el árbol completo.
     *
     * @return array{ mainNavItems: array<mixed>, footerNavItems: array<mixed> }
     */
    public function execute(?User $user = null, ?string $companyId = null): array
    {
        $disabledIds = $companyId !== null
            ? array_fill_keys($this->disabledMenus->disabledMenuIds($companyId), true)
            : [];

        return [
            'mainNavItems' => $this->buildSection('main', $user, $disabledIds),
            'footerNavItems' => $this->buildSection('footer', $user, $disabledIds),
        ];
    }

    /**
     * @param  array<string, true>  $disabledIds
     * @return array<int, array<string, mixed>>
     */
    private function buildSection(string $section, ?User $user, array $disabledIds): array
    {
        $menus = $this->repository->findActiveRootsBySection($section);

        return $this->buildNodes($menus, $user, $disabledIds);
    }

    /**
     * @param  iterable<Menu>  $menus
     * @param  array<string, true>  $disabledIds
     * @return array<int, array<string, mixed>>
     */
    private function buildNodes(iterable $menus, ?User $user, array $disabledIds): array
    {
        $nodes = [];

        foreach ($menus as $menu) {
            $node = $this->toArray($menu, $user, $disabledIds);

            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * Build a menu node, or return null when it must be hidden: the company
     * disabled it, the user lacks its permission, or it is an empty group — a
     * node with no URL of its own whose children were all filtered out.
     *
     * @param  array<string, true>  $disabledIds
     * @return array<string, mixed>|null
     */
    private function toArray(Menu $menu, ?User $user, array $disabledIds): ?array
    {
        if ($this->isDisabledForCompany($menu, $disabledIds)) {
            return null;
        }

        if ($user !== null && ! $this->isVisibleTo($menu, $user)) {
            return null;
        }

        $children = $this->buildNodes($menu->children, $user, $disabledIds);

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

    /** @param  array<string, true>  $disabledIds */
    private function isDisabledForCompany(Menu $menu, array $disabledIds): bool
    {
        if ($menu->permission === self::SYSTEM_OWNER_PERMISSION) {
            return false;
        }

        return isset($disabledIds[$menu->id]);
    }

    private function isVisibleTo(Menu $menu, User $user): bool
    {
        if (! $menu->permission) {
            return true;
        }

        if ($menu->permission === self::SYSTEM_OWNER_PERMISSION) {
            return (bool) $user->is_system_owner;
        }

        return $user->hasPermission($menu->permission);
    }
}
