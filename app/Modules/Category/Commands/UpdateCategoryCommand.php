<?php

declare(strict_types=1);

namespace App\Modules\Category\Commands;

use App\Modules\Category\Requests\UpdateCategoryRequest;

class UpdateCategoryCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly int $order = 0,
    ) {}

    public static function fromRequest(UpdateCategoryRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
            order: $request->integer('order'),
        );
    }
}
