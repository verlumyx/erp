<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\ConvertStoreOrderRequest;

class ConvertStoreOrderCommand
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $companyId,
        public readonly string $userId,
        public readonly ?string $clientId = null,
        public readonly string $createClient = 'no',
        public readonly ?string $documentType = null,
        public readonly ?string $documentNumber = null,
    ) {}

    public static function fromRequest(ConvertStoreOrderRequest $request, string $company, string $id): self
    {
        $nullable = static fn (mixed $value): ?string => $value === null || $value === '' ? null : (string) $value;
        $type = $nullable($request->input('document_type'));
        $number = $nullable($request->input('document_number'));

        return new self(
            orderId: $id,
            companyId: $company,
            userId: $request->user()->id,
            clientId: $nullable($request->input('client_id')),
            createClient: $request->string('create_client', 'no')->toString() === 'yes' ? 'yes' : 'no',
            documentType: $type === null ? null : strtoupper($type),
            documentNumber: $number === null ? null : preg_replace('/\D/', '', $number),
        );
    }
}
