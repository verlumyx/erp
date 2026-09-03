<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreSettingRepositoryInterface;

/**
 * Puerta de entrada a los ajustes de tienda de una empresa. Nunca falla por
 * ajustes ausentes: la primera vez que se abre la pantalla se estrenan con
 * los valores por defecto, igual que `app_configurations`.
 */
class StoreSettingFindOrCreateService
{
    public function __construct(
        private readonly StoreSettingRepositoryInterface $repository,
    ) {}

    public function execute(string $companyId, ?string $createdBy = null): StoreSetting
    {
        $settings = $this->repository->findByCompany($companyId);

        if ($settings !== null) {
            return $settings;
        }

        $this->repository->create($companyId, $createdBy);

        return $this->repository->findOrFailByCompany($companyId);
    }
}
