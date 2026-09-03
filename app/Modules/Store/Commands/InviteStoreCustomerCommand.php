<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

class InviteStoreCustomerCommand
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $companyId,
        public readonly string $invitedBy,
    ) {}
}
