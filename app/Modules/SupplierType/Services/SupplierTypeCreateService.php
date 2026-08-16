<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Services;

use App\Modules\SupplierType\Commands\CreateSupplierTypeCommand;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;

class SupplierTypeCreateService
{
    public function __construct(
        private readonly SupplierTypeRepositoryInterface $repository,
    ) {}

    public function execute(CreateSupplierTypeCommand $command): SupplierType
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
