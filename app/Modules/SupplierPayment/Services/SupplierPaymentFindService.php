<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\SupplierPayment\Exceptions\SupplierPaymentNotFoundException;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;

class SupplierPaymentFindService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): SupplierPayment
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierPaymentNotFoundException;
        }

        return $model;
    }
}
