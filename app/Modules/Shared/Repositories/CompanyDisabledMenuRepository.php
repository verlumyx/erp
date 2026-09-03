<?php

declare(strict_types=1);

namespace App\Modules\Shared\Repositories;

use App\Modules\Menu\Models\Menu;
use App\Modules\Shared\Models\CompanyDisabledMenu;
use App\Modules\Shared\Repositories\Contracts\CompanyDisabledMenuRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyDisabledMenuRepository implements CompanyDisabledMenuRepositoryInterface
{
    public function disabledMenuIds(string $companyId): array
    {
        return CompanyDisabledMenu::query()
            ->where('company_id', $companyId)
            ->pluck('menu_id')
            ->all();
    }

    public function disabledModuleNames(string $companyId): array
    {
        $disabledIds = $this->disabledMenuIds($companyId);

        if ($disabledIds === []) {
            return [];
        }

        return Menu::query()
            ->where(function ($query) use ($disabledIds): void {
                $query->whereIn('id', $disabledIds)
                    ->orWhereIn('parent_id', $disabledIds);
            })
            ->whereNotNull('permission')
            ->where('permission', '!=', 'system_owner')
            ->pluck('permission')
            ->map(fn (string $permission): string => Str::before($permission, '.'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Mismo patrón que los permisos de un rol: se reemplaza el conjunto
     * completo en una transacción.
     */
    public function sync(string $companyId, array $menuIds): void
    {
        DB::transaction(function () use ($companyId, $menuIds): void {
            CompanyDisabledMenu::query()->where('company_id', $companyId)->delete();

            foreach (array_values(array_unique($menuIds)) as $menuId) {
                CompanyDisabledMenu::create([
                    'company_id' => $companyId,
                    'menu_id' => $menuId,
                ]);
            }
        });
    }
}
