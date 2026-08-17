<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Repositories\Contracts;

use App\Modules\Configuration\Commands\CreateConfigurationCommand;
use App\Modules\Configuration\Commands\UpdateConfigurationCommand;
use App\Modules\Configuration\Models\Configuration;

interface ConfigurationRepositoryInterface
{
    public function create(CreateConfigurationCommand $command): void;

    public function findByCompany(string $companyId): ?Configuration;

    /**
     * Para leer la configuración recién escrita, cuando su ausencia sería un
     * fallo de escritura y no un caso a contemplar.
     */
    public function findOrFailByCompany(string $companyId): Configuration;

    public function update(Configuration $model, UpdateConfigurationCommand $command): void;
}
