<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Commands;

use App\Modules\PriceList\Requests\UpdatePriceListRequest;

class UpdatePriceListCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdatePriceListRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
        );
    }
}
