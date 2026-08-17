<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Services;

use App\Modules\Configuration\Commands\UpdateConfigurationCommand;
use App\Modules\Configuration\Models\Configuration;
use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;

class ConfigurationUpdateService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationFindService $findService,
    ) {}

    public function execute(string $companyId, UpdateConfigurationCommand $command): Configuration
    {
        $configuration = $this->findService->execute($companyId);

        $this->repository->update($configuration, $command);

        return $this->repository->findOrFailByCompany($companyId);
    }
}
