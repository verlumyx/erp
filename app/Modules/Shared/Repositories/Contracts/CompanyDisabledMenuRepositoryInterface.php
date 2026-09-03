<?php

declare(strict_types=1);

namespace App\Modules\Shared\Repositories\Contracts;

interface CompanyDisabledMenuRepositoryInterface
{
    /** @return string[] */
    public function disabledMenuIds(string $companyId): array;

    /**
     * Deja exactamente estos menús deshabilitados para la empresa.
     *
     * @param  string[]  $menuIds
     */
    public function sync(string $companyId, array $menuIds): void;
}
