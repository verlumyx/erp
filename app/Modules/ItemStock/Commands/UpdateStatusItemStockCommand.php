<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Commands;

use App\Modules\ItemStock\Requests\UpdateStatusItemStockRequest;

class UpdateStatusItemStockCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusItemStockRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
