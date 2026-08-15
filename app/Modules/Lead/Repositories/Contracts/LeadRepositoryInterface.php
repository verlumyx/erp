<?php

declare(strict_types=1);

namespace App\Modules\Lead\Repositories\Contracts;

use App\Modules\Lead\Commands\CreateLeadCommand;
use App\Modules\Lead\Models\Lead;

interface LeadRepositoryInterface
{
    public function create(CreateLeadCommand $command): Lead;
}
