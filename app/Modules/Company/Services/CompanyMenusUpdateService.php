<?php

declare(strict_types=1);

namespace App\Modules\Company\Services;

use App\Modules\Company\Commands\UpdateCompanyMenusCommand;
use App\Modules\Company\Exceptions\CompanyNotFoundException;
use App\Modules\Company\Repositories\Contracts\CompanyRepositoryInterface;
use App\Modules\Menu\Models\Menu;
use App\Modules\Shared\Repositories\Contracts\CompanyDisabledMenuRepositoryInterface;

class CompanyMenusUpdateService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $repository,
        private readonly CompanyDisabledMenuRepositoryInterface $disabledMenus,
    ) {}

    public function execute(string $companyId, UpdateCompanyMenusCommand $command): void
    {
        if ($this->repository->findById($companyId) === null) {
            throw new CompanyNotFoundException;
        }

        $this->disabledMenus->sync($companyId, $this->disableableIds($command->disabledMenus));
    }

    /**
     * El menú del dueño del sistema («Empresas») no se puede esconder a
     * ninguna empresa, así que se descarta aunque venga en la petición.
     *
     * @param  string[]  $menuIds
     * @return string[]
     */
    private function disableableIds(array $menuIds): array
    {
        if ($menuIds === []) {
            return [];
        }

        return Menu::query()
            ->whereIn('id', $menuIds)
            ->where(function ($query): void {
                $query->whereNull('permission')
                    ->orWhere('permission', '!=', 'system_owner');
            })
            ->pluck('id')
            ->all();
    }
}
