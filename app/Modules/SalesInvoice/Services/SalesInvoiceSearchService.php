<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Commands\SearchSalesInvoiceCommand;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;

class SalesInvoiceSearchService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSalesInvoiceCommand $command): array
    {
        return $this->repository->search($command);
    }
}
