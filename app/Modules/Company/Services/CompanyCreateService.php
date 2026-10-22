<?php

declare(strict_types=1);

namespace App\Modules\Company\Services;

use App\Modules\Client\Services\ClientProvisionService;
use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Repositories\Contracts\CompanyRepositoryInterface;
use App\Modules\Configuration\Commands\CreateConfigurationCommand;
use App\Modules\Configuration\Services\ConfigurationCreateService;
use App\Modules\MeasurementUnit\Services\MeasurementUnitProvisionService;
use App\Modules\PriceList\Services\PriceListProvisionService;
use App\Modules\Role\Commands\CreateRoleCommand;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;
use App\Modules\Shared\Commands\CreateUserCompanyCommand;
use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;
use App\Modules\Tax\Services\TaxProvisionService;
use App\Modules\Warehouse\Services\WarehouseProvisionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyCreateService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly UserCompanyRepositoryInterface $userCompanyRepository,
        private readonly ConfigurationCreateService $configurationCreateService,
        private readonly TaxProvisionService $taxProvisionService,
        private readonly MeasurementUnitProvisionService $measurementUnitProvisionService,
        private readonly PriceListProvisionService $priceListProvisionService,
        private readonly ClientProvisionService $clientProvisionService,
        private readonly WarehouseProvisionService $warehouseProvisionService,
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

            /** Impuestos oficiales de Venezuela (Exento 0%, General 16%, Reducido 8%, Adicional 31%). */
            $this->taxProvisionService->provisionDefaults($command->id, $command->createdBy);

            /** Unidades de medida básicas por defecto (Unidad, Servicio, Kilogramo, Litro, Caja). */
            $this->measurementUnitProvisionService->provisionDefaults($command->id, $command->createdBy);

            /** Lista de precios por defecto (Precio regular). */
            $priceList = $this->priceListProvisionService->provisionDefaults($command->id, $command->createdBy);

            /** Cliente genérico de mostrador (Consumidor Final, V-0000000). */
            $this->clientProvisionService->provisionDefaults($command->id, $command->createdBy, $priceList->id);

            /** Bodega general por defecto con control de ubicaciones. */
            $this->warehouseProvisionService->provisionDefaults($command->id, $command->createdBy);

            return $this->companyRepository->findOrFail($command->id);
        });
    }
}
