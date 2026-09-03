<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories\Contracts;

use App\Modules\Store\Commands\UpdateStoreSettingsCommand;
use App\Modules\Store\Models\StoreSetting;

interface StoreSettingRepositoryInterface
{
    public function create(string $companyId, ?string $createdBy): void;

    public function findByCompany(string $companyId): ?StoreSetting;

    /**
     * Para leer los ajustes recién escritos, cuando su ausencia sería un
     * fallo de escritura y no un caso a contemplar.
     */
    public function findOrFailByCompany(string $companyId): StoreSetting;

    /** La empresa dueña de una llave, buscada por su hash. */
    public function findByApiKeyHash(string $hash): ?StoreSetting;

    public function update(StoreSetting $model, UpdateStoreSettingsCommand $command): void;

    /** Guarda el hash de la llave nueva; la anterior queda invalidada. */
    public function writeApiKeyHash(StoreSetting $model, string $hash): void;

    public function writeLogoPath(StoreSetting $model, ?string $path): void;

    public function touchApiKeyLastUsedAt(StoreSetting $model): void;
}
