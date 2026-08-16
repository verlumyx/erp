<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Commands;

use App\Modules\ExchangeRate\Requests\UpdateExchangeRateRequest;

class UpdateExchangeRateCommand
{
    public function __construct(
        public readonly string $currency,
        public readonly string $rateDate,
        public readonly string $rate,
        public readonly string $type,
        public readonly ?string $source = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdateExchangeRateRequest $request): self
    {
        return new self(
            currency: $request->string('currency')->toString(),
            rateDate: $request->string('rate_date')->toString(),
            rate: $request->string('rate')->toString(),
            type: $request->string('type')->toString(),
            source: $request->input('source'),
            description: $request->input('description'),
        );
    }
}
