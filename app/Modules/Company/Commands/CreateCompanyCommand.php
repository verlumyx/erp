<?php

declare(strict_types=1);

namespace App\Modules\Company\Commands;

use App\Modules\Company\Requests\CreateCompanyRequest;

class CreateCompanyCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $createdBy,
        public readonly ?string $rif = null,
        public readonly ?string $address = null,
        public readonly ?string $fiscalAddress = null,
        public readonly ?string $phone = null,
        public readonly ?string $taxpayerType = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreateCompanyRequest $request): self
    {
        return new self(
            id: $request->string('id')->toString(),
            name: $request->string('name')->toString(),
            createdBy: $request->user()->id,
            rif: $request->input('rif'),
            address: $request->input('address'),
            fiscalAddress: $request->input('fiscal_address'),
            phone: $request->input('phone'),
            taxpayerType: $request->input('taxpayer_type', 'ordinary'),
            description: $request->input('description'),
        );
    }
}
