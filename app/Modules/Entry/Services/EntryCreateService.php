<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;

class EntryCreateService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly EntryLimitsService $limits,
    ) {}

    /**
     * La entrada se valora con la tasa de **su** fecha, no con la de la orden
     * que la origina: son dos documentos y cada uno congela la suya. Si falta
     * la tasa del día la entrada no se registra.
     */
    public function execute(CreateEntryCommand $command): Entry
    {
        $this->limits->guard(
            $command->sourceableType,
            $command->sourceableId,
            $command->supplierId,
            $command->warehouseId,
            $command->entryType,
            $command->companyId,
            $command->lines,
            $command->allowsOverReceipt,
            $command->id,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->entryDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }
}
