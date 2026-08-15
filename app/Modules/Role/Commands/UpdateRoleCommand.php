<?php

declare(strict_types=1);

namespace App\Modules\Role\Commands;

use App\Modules\Role\Requests\UpdateRoleRequest;

class UpdateRoleCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $permissionType,
        public readonly ?string $companyId = null,
        public readonly ?string $description = null,
        /** @var array<string> */
        public readonly array $permissions = [],
    ) {}

    public static function fromRequest(UpdateRoleRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            permissionType: $request->string('permission_type')->toString(),
            companyId: $request->input('company_id'),
            description: $request->input('description'),
            permissions: $request->input('permissions', []),
        );
    }
}
