<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Services;

use App\Modules\Configuration\Commands\CreateConfigurationCommand;
use App\Modules\Configuration\Models\Configuration;
use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;

/**
 * Puerta de entrada a la configuración de una empresa. Es la que consultan el
 * resto de módulos para saber en qué moneda trabajar.
 */
class ConfigurationFindService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
    ) {}

    /**
     * Nunca falla por configuración ausente: las empresas creadas antes de
     * este módulo estrenan la suya con los valores por defecto.
     */
    public function execute(string $companyId): Configuration
    {
        $configuration = $this->repository->findByCompany($companyId);

        if ($configuration !== null) {
            return $configuration;
        }

        $this->repository->create(CreateConfigurationCommand::defaults($companyId));

        return $this->repository->findOrFailByCompany($companyId);
    }
}
