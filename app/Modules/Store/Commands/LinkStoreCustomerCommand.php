<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\LinkStoreCustomerRequest;

class LinkStoreCustomerCommand
{
    public function __construct(
        public readonly string $storeCustomerId,
        public readonly string $companyId,
        public readonly string $clientId,
        public readonly string $linkedBy,
    ) {}

    public static function fromRequest(LinkStoreCustomerRequest $request, string $company, string $id): self
    {
        return new self(
            storeCustomerId: $id,
            companyId: $company,
            clientId: $request->string('client_id')->toString(),
            linkedBy: $request->user()->id,
        );
    }
}
