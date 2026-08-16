<?php

declare(strict_types=1);

namespace App\Modules\Tax\Commands;

use App\Modules\Tax\Requests\CreateTaxRequest;

class CreateTaxCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $percentage,
        public readonly string $hasWithholding,
        public readonly string $withholdingPercentage,
        public readonly string $createdBy,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreateTaxRequest $request, ?string $companyId = null): self
    {
        $hasWithholding = $request->string('has_withholding')->toString() ?: 'no';

        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            percentage: (string) $request->input('percentage', 0),
            hasWithholding: $hasWithholding,
            withholdingPercentage: $hasWithholding === 'yes'
                ? (string) $request->input('withholding_percentage', 0)
                : '0',
            createdBy: $request->user()->id,
            description: $request->input('description'),
        );
    }
}
