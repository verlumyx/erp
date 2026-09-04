<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Exceptions\ImportNotFoundException;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;

class ImportFindService
{
    public function __construct(
        private readonly ImportRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Import
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ImportNotFoundException;
        }

        return $model;
    }
}
