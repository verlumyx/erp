<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Commands;

use App\Modules\Currency\Models\Currency;
use Illuminate\Support\Str;

class CreateConfigurationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $baseCurrency,
        public readonly ?string $secondaryCurrency,
        public readonly string $rateType,
        public readonly string $allowsRateOverride,
        public readonly int $amountDecimals,
        public readonly int $priceDecimals,
        /** Impacto a partir del cual un ajuste necesita una segunda firma. */
        public readonly float $adjustmentApprovalThreshold = 0.0,
        public readonly ?string $createdBy = null,
    ) {}

    /**
     * Configuración inicial de una empresa recién creada: cifras en dólares
     * con presentación obligatoria en bolívares, que es el caso habitual.
     */
    public static function defaults(string $companyId, ?string $createdBy = null): self
    {
        return new self(
            id: (string) Str::uuid7(),
            companyId: $companyId,
            baseCurrency: 'USD',
            secondaryCurrency: Currency::LOCAL_CODE,
            rateType: 'legal',
            allowsRateOverride: 'yes',
            amountDecimals: 2,
            priceDecimals: 6,
            adjustmentApprovalThreshold: 0.0,
            createdBy: $createdBy,
        );
    }
}
