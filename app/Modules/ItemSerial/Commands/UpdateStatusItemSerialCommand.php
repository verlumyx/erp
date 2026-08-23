<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Commands;

use App\Modules\ItemSerial\Requests\UpdateStatusItemSerialRequest;

class UpdateStatusItemSerialCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusItemSerialRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
