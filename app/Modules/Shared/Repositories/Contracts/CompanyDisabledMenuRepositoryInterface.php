<?php

declare(strict_types=1);

namespace App\Modules\Shared\Repositories\Contracts;

interface CompanyDisabledMenuRepositoryInterface
{
    /** @return string[] */
    public function disabledMenuIds(string $companyId): array;

    /**
     * Módulos cuyos menús la empresa no ve, deducidos del permiso del menú
     * (`store-items.list` → `store-items`). Un grupo deshabilitado arrastra a
     * sus hijos.
     *
     * @return string[]
     */
    public function disabledModuleNames(string $companyId): array;

    /**
     * Deja exactamente estos menús deshabilitados para la empresa.
     *
     * @param  string[]  $menuIds
     */
    public function sync(string $companyId, array $menuIds): void;
}
