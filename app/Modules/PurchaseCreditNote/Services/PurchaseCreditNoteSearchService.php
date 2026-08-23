<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\PurchaseCreditNote\Commands\SearchPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;

class PurchaseCreditNoteSearchService
{
    public function __construct(
        private readonly PurchaseCreditNoteRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPurchaseCreditNoteCommand $command): array
    {
        return $this->repository->search($command);
    }
}
