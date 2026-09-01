<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Exceptions\EntryNotFoundException;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;

class EntryFindService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Entry
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new EntryNotFoundException;
        }

        return $model;
    }
}
