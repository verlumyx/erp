<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Commands;

use App\Modules\ExchangeRate\Requests\CreateExchangeRateRequest;

class CreateExchangeRateCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $currency,
        public readonly string $rateDate,
        public readonly string $rate,
        public readonly string $type,
        public readonly string $createdBy,
        public readonly ?string $source = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreateExchangeRateRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            currency: $request->string('currency')->toString(),
            rateDate: $request->string('rate_date')->toString(),
            rate: $request->string('rate')->toString(),
            type: $request->string('type')->toString(),
            createdBy: $request->user()->id,
            source: $request->input('source'),
            description: $request->input('description'),
        );
    }
}
