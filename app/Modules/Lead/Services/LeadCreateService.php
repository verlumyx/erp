<?php

declare(strict_types=1);

namespace App\Modules\Lead\Services;

use App\Modules\Lead\Commands\CreateLeadCommand;
use App\Modules\Lead\Models\Lead;
use App\Modules\Lead\Repositories\Contracts\LeadRepositoryInterface;

class LeadCreateService
{
    public function __construct(
        private readonly LeadRepositoryInterface $repository,
    ) {}

    public function execute(CreateLeadCommand $command): Lead
    {
        return $this->repository->create($command);
    }
}
