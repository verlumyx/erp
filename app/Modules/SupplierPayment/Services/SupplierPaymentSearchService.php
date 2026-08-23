<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\SupplierPayment\Commands\SearchSupplierPaymentCommand;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;

class SupplierPaymentSearchService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSupplierPaymentCommand $command): array
    {
        return $this->repository->search($command);
    }
}
