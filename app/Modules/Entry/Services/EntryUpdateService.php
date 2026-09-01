<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\UpdateEntryCommand;
use App\Modules\Entry\Exceptions\EntryNotFoundException;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;

class EntryUpdateService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly EntryLimitsService $limits,
    ) {}

    /**
     * Una entrada solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo y vuelve a repartir los gastos entre las líneas. Al
     * confirmarla quedan congelados: es el costo con el que la mercancía entró
     * y ya nada lo recalcula.
     */
    public function execute(string $id, UpdateEntryCommand $command, ?string $companyId = null): Entry
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new EntryNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->limits->guard(
            $command->sourceableType,
            $command->sourceableId,
            $command->supplierId,
            $command->warehouseId,
            $command->entryType,
            $company,
            $command->lines,
            $command->allowsOverReceipt,
            $model->id,
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->entryDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
    }
}
