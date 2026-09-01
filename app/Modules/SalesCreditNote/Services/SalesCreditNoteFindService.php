<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\SalesCreditNote\Exceptions\SalesCreditNoteNotFoundException;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;

class SalesCreditNoteFindService
{
    public function __construct(
        private readonly SalesCreditNoteRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): SalesCreditNote
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesCreditNoteNotFoundException;
        }

        return $model;
    }
}
