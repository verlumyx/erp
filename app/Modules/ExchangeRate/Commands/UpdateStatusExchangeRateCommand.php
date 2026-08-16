<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Commands;

use App\Modules\ExchangeRate\Requests\UpdateStatusExchangeRateRequest;

class UpdateStatusExchangeRateCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusExchangeRateRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
