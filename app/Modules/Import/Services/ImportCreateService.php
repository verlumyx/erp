<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Import\Commands\CreateImportCommand;
use App\Modules\Import\Commands\ImportEntryData;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;

class ImportCreateService
{
    public function __construct(
        private readonly ImportRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly ImportCostingService $costing,
        private readonly ImportLimitsService $limits,
    ) {}

    /**
     * El expediente nace con el reparto ya hecho: nada de lo que lo compone
     * —ni la tasa de cada cargo, ni los ítems, ni lo que le tocó a cada uno—
     * viaja desde la pantalla. Se resuelve aquí, que es lo que hace que el
     * documento pruebe algo.
     */
    public function execute(CreateImportCommand $command): Import
    {
        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->importDate,
            $command->exchangeRateOverride,
        );

        $entryIds = ImportEntryData::activeIds($command->entries);

        $costing = $this->costing->resolve(
            $command->companyId,
            $command->warehouseId,
            $command->importDate,
            $command->allocationMethod,
            $rates,
            $command->costs,
            $entryIds,
            $command->lineStatuses,
        );

        $this->limits->guard(
            $command->companyId,
            $command->warehouseId,
            $command->allocationMethod,
            $entryIds,
            $costing,
        );

        $this->repository->create($command, $rates, $costing);

        return $this->repository->findOrFail($command->id);
    }
}
