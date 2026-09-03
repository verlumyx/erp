<?php

declare(strict_types=1);

namespace App\Modules\Shared\Repositories;

use App\Modules\Shared\Models\CompanyDisabledMenu;
use App\Modules\Shared\Repositories\Contracts\CompanyDisabledMenuRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CompanyDisabledMenuRepository implements CompanyDisabledMenuRepositoryInterface
{
    public function disabledMenuIds(string $companyId): array
    {
        return CompanyDisabledMenu::query()
            ->where('company_id', $companyId)
            ->pluck('menu_id')
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
