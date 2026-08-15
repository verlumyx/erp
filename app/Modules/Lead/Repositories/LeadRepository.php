<?php

declare(strict_types=1);

namespace App\Modules\Lead\Repositories;

use App\Modules\Lead\Commands\CreateLeadCommand;
use App\Modules\Lead\Models\Lead;
use App\Modules\Lead\Repositories\Contracts\LeadRepositoryInterface;

class LeadRepository implements LeadRepositoryInterface
{
    public function create(CreateLeadCommand $command): Lead
    {
        return Lead::create([
            'id' => $command->id,
            'name' => $command->name,
            'email' => $command->email,
            'phone' => $command->phone,
            'status' => Lead::STATUS_PENDING,
        ]);
    }
}
