<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Commands;

use App\Modules\PriceList\Requests\CreatePriceListRequest;

class CreatePriceListCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $createdBy,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreatePriceListRequest $request, ?string $companyId = null): self
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
