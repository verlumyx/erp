<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Commands;

use App\Modules\SupplierType\Requests\UpdateSupplierTypeRequest;

class UpdateSupplierTypeCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdateSupplierTypeRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
        );
    }
}
