<?php

declare(strict_types=1);

namespace App\Modules\Permission\Commands;

use App\Modules\Permission\Requests\CreatePermissionRequest;

class CreatePermissionCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $moduleId,
        public readonly string $action,
        public readonly string $label,
        public readonly ?string $description = null,
        public readonly bool $isActive = true,
        public readonly int $order = 0,
    ) {}

    public static function fromRequest(CreatePermissionRequest $request): self
    {
        return new self(
            id: $request->string('id')->toString(),
            moduleId: $request->string('module_id')->toString(),
            action: $request->string('action')->toString(),
            label: $request->string('label')->toString(),
            description: $request->input('description'),
            isActive: $request->boolean('is_active', true),
            order: $request->integer('order', 0),
        );
    }
}
