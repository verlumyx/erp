<?php

declare(strict_types=1);

namespace App\Modules\Company\Services;

use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Repositories\Contracts\CompanyRepositoryInterface;
use App\Modules\Configuration\Commands\CreateConfigurationCommand;
use App\Modules\Configuration\Services\ConfigurationCreateService;
use App\Modules\Role\Commands\CreateRoleCommand;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;
use App\Modules\Shared\Commands\CreateUserCompanyCommand;
use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyCreateService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly UserCompanyRepositoryInterface $userCompanyRepository,
        private readonly ConfigurationCreateService $configurationCreateService,
    ) {}

    public function execute(CreateCompanyCommand $command): Company
    {
        return DB::transaction(function () use ($command): Company {
            $this->companyRepository->create($command);

            $roleId = Str::uuid7()->toString();

            $this->roleRepository->create(new CreateRoleCommand(
                id: $roleId,
                name: 'Administrador',
                permissionType: 'all',
                companyId: $command->id,
                description: 'Rol administrador de la empresa',
            ));

            $this->userCompanyRepository->create(new CreateUserCompanyCommand(
                id: Str::uuid7()->toString(),
                userId: $command->createdBy,
                companyId: $command->id,
                roleId: $roleId,
                status: 'active',
                isDefault: true,
            ));

            /** Ninguna empresa existe sin configuración: nace con los valores por defecto. */
            $this->configurationCreateService->execute(
                CreateConfigurationCommand::defaults($command->id, $command->createdBy),
            );

            return $this->companyRepository->findOrFail($command->id);
        });
    }
}
