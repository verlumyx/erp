<?php

declare(strict_types=1);

namespace App\Modules\Service\Commands;

use App\Modules\Service\Requests\UpdateStatusServiceRequest;

class UpdateStatusServiceCommand
{
    public function __construct(
        public readonly bool $active,
    ) {}

    public static function fromRequest(UpdateStatusServiceRequest $request): self
    {
        return new self(
            active: $request->boolean('active'),
        );
    }
}
