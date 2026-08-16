<?php

declare(strict_types=1);

namespace App\Modules\Category\Commands;

use App\Modules\Category\Requests\UpdateStatusCategoryRequest;

class UpdateStatusCategoryCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusCategoryRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
