<?php

declare(strict_types=1);

namespace App\Modules\Company\Commands;

use App\Modules\Company\Requests\UpdateCompanyRequest;

class UpdateCompanyCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $rif = null,
        public readonly ?string $address = null,
        public readonly ?string $fiscalAddress = null,
        public readonly ?string $phone = null,
        public readonly ?string $taxpayerType = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdateCompanyRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            rif: $request->input('rif'),
            address: $request->input('address'),
            fiscalAddress: $request->input('fiscal_address'),
            phone: $request->input('phone'),
            taxpayerType: $request->input('taxpayer_type'),
            description: $request->input('description'),
        );
    }
}
