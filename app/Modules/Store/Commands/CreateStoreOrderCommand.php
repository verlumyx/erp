<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\Api\CreateStoreOrderApiRequest;
use Illuminate\Support\Str;

class CreateStoreOrderCommand
{
    /**
     * @param  array<int, StoreOrderLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $storeCustomerId,
        public readonly array $lines,
        public readonly ?string $clientAddressId = null,
        public readonly ?string $deliveryAddress = null,
        public readonly ?string $deliveryCity = null,
        public readonly ?string $deliveryState = null,
        public readonly ?string $buyerNotes = null,
        /** Lo que sigue lo llena el servicio, no la tienda. */
        public readonly ?string $clientId = null,
        public readonly string $buyerName = '',
        public readonly ?string $buyerDocumentType = null,
        public readonly ?string $buyerDocumentNumber = null,
        public readonly string $buyerEmail = '',
        public readonly string $buyerPhone = '',
        public readonly string $currency = 'USD',
        public readonly float $exchangeRate = 1.0,
    ) {}

    public static function fromRequest(CreateStoreOrderApiRequest $request, string $companyId, string $storeCustomerId): self
    {
        $nullable = static fn (mixed $value): ?string => $value === null || $value === '' ? null : (string) $value;

        return new self(
            id: (string) Str::uuid7(),
            companyId: $companyId,
            storeCustomerId: $storeCustomerId,
            lines: StoreOrderLineData::collection($request->input('lines', [])),
            clientAddressId: $nullable($request->input('client_address_id')),
            deliveryAddress: $nullable($request->input('delivery_address')),
            deliveryCity: $nullable($request->input('delivery_city')),
            deliveryState: $nullable($request->input('delivery_state')),
            buyerNotes: $nullable($request->input('buyer_notes')),
        );
    }

    /**
     * El mismo pedido con el comprador copiado, las líneas valoradas y la
     * moneda y la tasa del momento.
     *
     * @param  array<int, StoreOrderLineData>  $lines
     */
    public function resolved(
        array $lines,
        ?string $clientId,
        ?string $clientAddressId,
        string $buyerName,
        ?string $buyerDocumentType,
        ?string $buyerDocumentNumber,
        string $buyerEmail,
        string $buyerPhone,
        string $currency,
        float $exchangeRate,
    ): self {
        return new self(
            id: $this->id,
            companyId: $this->companyId,
            storeCustomerId: $this->storeCustomerId,
            lines: $lines,
            clientAddressId: $clientAddressId,
            deliveryAddress: $this->deliveryAddress,
            deliveryCity: $this->deliveryCity,
            deliveryState: $this->deliveryState,
            buyerNotes: $this->buyerNotes,
            clientId: $clientId,
            buyerName: $buyerName,
            buyerDocumentType: $buyerDocumentType,
            buyerDocumentNumber: $buyerDocumentNumber,
            buyerEmail: $buyerEmail,
            buyerPhone: $buyerPhone,
            currency: $currency,
            exchangeRate: $exchangeRate,
        );
    }
}
