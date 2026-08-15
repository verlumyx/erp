<?php

declare(strict_types=1);

namespace App\Modules\Sale\Commands;

use App\Modules\Sale\Requests\ReactivateSaleRequest;

class ReactivateSaleCommand
{
    /**
     * @param  array<int, string>  $profileIds  Profiles a reasignar; vacío = reusar los originales.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $saleId,
        public readonly ?string $companyId,
        public readonly ?int $durationDays,
        public readonly ?float $price,
        public readonly ?string $renewedBy,
        public readonly array $profileIds = [],
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(
        ReactivateSaleRequest $request,
        string $saleId,
        ?string $companyId = null,
        ?string $renewedBy = null,
    ): self {
        return new self(
            id: $request->string('id')->toString(),
            saleId: $saleId,
            companyId: $companyId,
            durationDays: $request->input('duration_days') !== null ? (int) $request->input('duration_days') : null,
            price: $request->input('price') !== null ? (float) $request->input('price') : null,
            renewedBy: $renewedBy,
            profileIds: array_values(array_map('strval', $request->input('profile_ids', []))),
            notes: $request->input('notes'),
        );
    }
}
