<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Commands;

use App\Modules\ItemLot\Requests\UpdateStatusItemLotRequest;

class UpdateStatusItemLotCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusItemLotRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
