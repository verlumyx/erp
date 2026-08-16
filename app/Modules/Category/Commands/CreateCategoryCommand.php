<?php

declare(strict_types=1);

namespace App\Modules\Category\Commands;

use App\Modules\Category\Requests\CreateCategoryRequest;

class CreateCategoryCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $createdBy,
        public readonly ?string $description = null,
        public readonly int $order = 0,
    ) {}

    public static function fromRequest(CreateCategoryRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            createdBy: $request->user()->id,
            description: $request->input('description'),
            order: $request->integer('order'),
        );
    }
}
