<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Import\Commands\ImportEntryData;
use App\Modules\Import\Commands\UpdateImportCommand;
use App\Modules\Import\Exceptions\ImportNotFoundException;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;

class ImportUpdateService
{
    public function __construct(
        private readonly ImportRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly ImportCostingService $costing,
        private readonly ImportLimitsService $limits,
    ) {}

    /**
     * Un expediente solo se edita en borrador, así que cada guardado vuelve a
     * repartir: el borrador que se guarda enseña el costeo de ese instante. El
     * que cuenta se resuelve otra vez al confirmar, que es cuando el
     * expediente manda revalorizar.
     */
    public function execute(string $id, UpdateImportCommand $command, ?string $companyId = null): Import
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ImportNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $rates = $this->rates->forDocument(
            (string) $company,
            $command->currency,
            $command->importDate,
            $command->exchangeRateOverride,
        );

        $entryIds = ImportEntryData::activeIds($command->entries);

        $costing = $this->costing->resolve(
            $company,
            $command->warehouseId,
            $command->importDate,
            $command->allocationMethod,
            $rates,
            $command->costs,
            $entryIds,
            $command->lineStatuses,
        );

        $this->limits->guard(
            $company,
            $command->warehouseId,
            $command->allocationMethod,
            $entryIds,
            $costing,
            $model->id,
        );

        $this->repository->update($model, $command, $rates, $costing);

        return $this->repository->findOrFail($id, $companyId);
    }
}
