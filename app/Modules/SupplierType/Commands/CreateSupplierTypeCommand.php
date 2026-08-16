<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Commands;

use App\Modules\SupplierType\Requests\CreateSupplierTypeRequest;

class CreateSupplierTypeCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $createdBy,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreateSupplierTypeRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            createdBy: $request->user()->id,
            description: $request->input('description'),
        );
    }
}
