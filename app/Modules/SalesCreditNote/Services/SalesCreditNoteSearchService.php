<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\SalesCreditNote\Commands\SearchSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;

class SalesCreditNoteSearchService
{
    public function __construct(
        private readonly SalesCreditNoteRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSalesCreditNoteCommand $command): array
    {
        return $this->repository->search($command);
    }
}
