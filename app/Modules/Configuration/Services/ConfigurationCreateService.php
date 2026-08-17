<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Services;

use App\Modules\Configuration\Commands\CreateConfigurationCommand;
use App\Modules\Configuration\Models\Configuration;
use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;

/**
 * No se invoca desde ninguna pantalla: lo llama la creación de la empresa. La
 * configuración nunca la crea el usuario a mano.
 */
class ConfigurationCreateService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
    ) {}

    public function execute(CreateConfigurationCommand $command): Configuration
    {
        $this->repository->create($command);

        return $this->repository->findOrFailByCompany($command->companyId);
    }
}
