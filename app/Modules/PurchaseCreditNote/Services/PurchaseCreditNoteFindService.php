<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\PurchaseCreditNote\Exceptions\PurchaseCreditNoteNotFoundException;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;

class PurchaseCreditNoteFindService
{
    public function __construct(
        private readonly PurchaseCreditNoteRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): PurchaseCreditNote
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseCreditNoteNotFoundException;
        }

        return $model;
    }
}
