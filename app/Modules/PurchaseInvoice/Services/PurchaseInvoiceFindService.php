<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceNotFoundException;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;

class PurchaseInvoiceFindService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): PurchaseInvoice
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseInvoiceNotFoundException;
        }

        return $model;
    }
}
