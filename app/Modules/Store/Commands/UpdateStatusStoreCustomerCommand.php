<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\UpdateStatusStoreCustomerRequest;

class UpdateStatusStoreCustomerCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusStoreCustomerRequest $request): self
    {
        return new self(status: $request->string('status')->toString());
    }
}
