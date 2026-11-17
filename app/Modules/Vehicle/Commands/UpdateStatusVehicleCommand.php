<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Commands;

use App\Modules\Vehicle\Requests\UpdateStatusVehicleRequest;

class UpdateStatusVehicleCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusVehicleRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
