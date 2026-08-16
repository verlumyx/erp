<?php

declare(strict_types=1);

namespace App\Modules\Tax\Commands;

use App\Modules\Tax\Requests\UpdateTaxRequest;

class UpdateTaxCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $percentage,
        public readonly string $hasWithholding,
        public readonly string $withholdingPercentage,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdateTaxRequest $request): self
    {
        $hasWithholding = $request->string('has_withholding')->toString() ?: 'no';

        return new self(
            name: $request->string('name')->toString(),
            percentage: (string) $request->input('percentage', 0),
            hasWithholding: $hasWithholding,
            withholdingPercentage: $hasWithholding === 'yes'
                ? (string) $request->input('withholding_percentage', 0)
                : '0',
            description: $request->input('description'),
        );
    }
}
