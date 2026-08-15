<?php

declare(strict_types=1);

namespace App\Modules\Shared\Commands;

class CreateUserCompanyCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $companyId,
        public readonly ?string $roleId = null,
        public readonly string $status = 'active',
        public readonly bool $isDefault = false,
    ) {}
}
