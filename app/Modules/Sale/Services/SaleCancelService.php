<?php

declare(strict_types=1);

namespace App\Modules\Sale\Services;

use App\Modules\Refund\Commands\CreateRefundCommand;
use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;
use App\Modules\Sale\Commands\CancelSaleCommand;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleCancelService
{
    public function __construct(
        private readonly SaleRepositoryInterface $repository,
        private readonly RefundRepositoryInterface $refundRepository,
    ) {}

    public function execute(CancelSaleCommand $command): Sale
    {
        return DB::transaction(function () use ($command): Sale {
            $sale = $this->repository->findOrFail($command->saleId, $command->companyId);

            $this->repository->cancel($sale, $command);

            if ($command->createRefund) {
                $this->refundRepository->create(new CreateRefundCommand(
                    id: Str::uuid7()->toString(),
                    companyId: $command->companyId,
                    saleId: $sale->id,
                    amount: $command->refundAmount ?? (float) $sale->price,
                    reason: $command->refundReason ?? $command->cancellationReason,
                    requestedBy: $command->requestedBy,
                ));
            }

            return $this->repository->findOrFail($command->saleId, $command->companyId);
        });
    }
}
