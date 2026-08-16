<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Commands\CreateSupplierCommand;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class SupplierCreateService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
    ) {}

    public function execute(CreateSupplierCommand $command): Supplier
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
