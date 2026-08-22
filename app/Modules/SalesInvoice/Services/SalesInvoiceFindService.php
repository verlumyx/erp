<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Exceptions\SalesInvoiceNotFoundException;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;

class SalesInvoiceFindService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): SalesInvoice
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesInvoiceNotFoundException;
        }

        return $model;
    }
}
