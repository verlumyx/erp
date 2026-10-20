<?php

declare(strict_types=1);

namespace App\Modules\Pos\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Pos\Commands\CreatePosCashMovementCommand;
use App\Modules\Pos\Exceptions\PosSessionClosedException;
use App\Modules\Pos\Models\PosCashMovement;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;

class PosCashMovementService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    public function execute(CreatePosCashMovementCommand $command): PosCashMovement
    {
        $session = $this->sessionRepository->findOrFail($command->posSessionId, $command->companyId);

        if (! $session->isOpen()) {
            throw new PosSessionClosedException;
        }

        $rateData = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            now()->toDateString(),
            $command->exchangeRate !== null ? (string) $command->exchangeRate : null,
        );

        $rate = (float) $rateData->exchangeRate;

        return $this->sessionRepository->recordCashMovement($command, $rate);
    }
}
