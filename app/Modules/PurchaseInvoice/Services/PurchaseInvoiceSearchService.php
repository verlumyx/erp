<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Commands\SearchPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;

class PurchaseInvoiceSearchService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPurchaseInvoiceCommand $command): array
    {
        return $this->repository->search($command);
    }
}
