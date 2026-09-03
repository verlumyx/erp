<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\RejectStoreOrderCommand;
use App\Modules\Store\Exceptions\StoreOrderNotPendingException;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;

class StoreOrderRejectService
{
    public function __construct(
        private readonly StoreOrderRepositoryInterface $repository,
        private readonly StoreOrderFindService $findService,
    ) {}

    public function execute(RejectStoreOrderCommand $command): StoreOrder
    {
        $order = $this->findService->execute($command->orderId, $command->companyId);

        if ($order->status !== 'pending') {
            throw StoreOrderNotPendingException::forOrder((string) $order->code);
        }

        $this->repository->writeRejected($order, $command->rejectionReason);

        return $this->repository->findOrFail($order->id, $command->companyId);
    }
}
