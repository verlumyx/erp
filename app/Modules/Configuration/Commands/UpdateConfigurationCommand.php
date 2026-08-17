<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Commands;

use App\Modules\Configuration\Requests\UpdateConfigurationRequest;

class UpdateConfigurationCommand
{
    public function __construct(
        public readonly string $baseCurrency,
        public readonly ?string $secondaryCurrency,
        public readonly string $rateType,
        public readonly string $allowsRateOverride,
        public readonly int $amountDecimals,
        public readonly int $priceDecimals,
    ) {}

    public static function fromRequest(UpdateConfigurationRequest $request): self
    {
        $secondary = $request->input('secondary_currency');

        return new self(
            baseCurrency: strtoupper($request->string('base_currency')->toString()),
            secondaryCurrency: $secondary === null || $secondary === ''
                ? null
                : strtoupper((string) $secondary),
            rateType: $request->string('rate_type')->toString(),
            allowsRateOverride: $request->string('allows_rate_override')->toString(),
            amountDecimals: $request->integer('amount_decimals'),
            priceDecimals: $request->integer('price_decimals'),
        );
    }
}
