<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\CreateStoreItemRequest;

class CreateStoreItemCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $itemId,
        public readonly string $createdBy,
        /** Vacío = se toma el nombre del artículo. */
        public readonly ?string $title = null,
        /** Vacío = se genera del título. */
        public readonly ?string $slug = null,
        public readonly ?string $summary = null,
        /** Vacío = se toma la descripción del artículo. */
        public readonly ?string $description = null,
        public readonly string $isFeatured = 'no',
        public readonly int $order = 0,
    ) {}

    public static function fromRequest(CreateStoreItemRequest $request, ?string $companyId = null): self
    {
        $nullable = static fn (mixed $value): ?string => $value === null || trim((string) $value) === '' ? null : (string) $value;

        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? (string) $request->route('company'),
            itemId: $request->string('item_id')->toString(),
            createdBy: $request->user()->id,
            title: $nullable($request->input('title')),
            slug: $nullable($request->input('slug')),
            summary: $nullable($request->input('summary')),
            description: $nullable($request->input('description')),
            isFeatured: $request->string('is_featured', 'no')->toString(),
            order: $request->integer('order'),
        );
    }
}
