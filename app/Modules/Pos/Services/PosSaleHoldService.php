<?php

declare(strict_types=1);

namespace App\Modules\Pos\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Pos\Commands\HoldPosSaleCommand;
use App\Modules\Pos\Exceptions\PosSessionClosedException;
use App\Modules\Pos\Exceptions\PosUnauthorizedSessionException;
use App\Modules\Pos\Models\PosSale;
use App\Modules\Pos\Repositories\Contracts\PosSaleRepositoryInterface;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;

class PosSaleHoldService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly PosSaleRepositoryInterface $saleRepository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    public function hold(HoldPosSaleCommand $command): PosSale
    {
        $session = $this->sessionRepository->findOrFail($command->posSessionId, $command->companyId);

        if (! $session->isOpen()) {
            throw new PosSessionClosedException;
        }

        if ($session->user_id !== $command->userId) {
            throw new PosUnauthorizedSessionException;
        }

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            now()->toDateString(),
            $command->exchangeRate !== null ? (string) $command->exchangeRate : null,
        );

        $draft = $this->saleRepository->createDraft($command, (float) $rates->exchangeRate);
        $draft->update(['pos_register_id' => $session->pos_register_id]);

        return $draft->refresh();
    }

    /**
     * @return array<int, PosSale>
     */
    public function listHeld(string $sessionId): array
    {
        return $this->saleRepository->getHeldSales($sessionId);
    }

    public function discard(string $saleId, ?string $companyId = null): void
    {
        $sale = $this->saleRepository->findOrFail($saleId, $companyId);
        $this->saleRepository->deleteDraft($sale);
    }
}
