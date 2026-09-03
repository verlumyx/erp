<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\UpdateStoreItemRequest;

class UpdateStoreItemCommand
{
    public function __construct(
        public readonly string $title,
        /** Vacío = se conserva el actual. */
        public readonly ?string $slug,
        public readonly ?string $summary,
        public readonly ?string $description,
        public readonly string $isFeatured,
        public readonly int $order,
    ) {}

    public static function fromRequest(UpdateStoreItemRequest $request): self
    {
        $nullable = static fn (mixed $value): ?string => $value === null || trim((string) $value) === '' ? null : (string) $value;

        return new self(
            title: $request->string('title')->toString(),
            slug: $nullable($request->input('slug')),
            summary: $nullable($request->input('summary')),
            description: $nullable($request->input('description')),
            isFeatured: $request->string('is_featured', 'no')->toString(),
            order: $request->integer('order'),
        );
    }
}
