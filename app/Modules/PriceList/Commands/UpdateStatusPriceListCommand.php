<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Commands;

use App\Modules\PriceList\Requests\UpdateStatusPriceListRequest;

class UpdateStatusPriceListCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusPriceListRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
