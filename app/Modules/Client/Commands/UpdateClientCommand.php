<?php

declare(strict_types=1);

namespace App\Modules\Client\Commands;

use App\Modules\Client\Requests\UpdateClientRequest;

class UpdateClientCommand
{
    /**
     * @param  array<int, ClientContactData>  $contacts
     * @param  array<int, ClientAddressData>  $addresses
     */
    public function __construct(
        public readonly string $name,
        public readonly string $documentType,
        public readonly string $documentNumber,
        public readonly array $contacts = [],
        public readonly array $addresses = [],
        public readonly ?string $clientTypeId = null,
        public readonly ?string $priceListId = null,
        public readonly ?string $legalName = null,
        public readonly ?string $phone = null,
        public readonly ?string $mobile = null,
        public readonly ?string $email = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $country = null,
        public readonly int $paymentTermDays = 0,
        public readonly string $creditLimit = '0',
        public readonly string $creditBlocked = 'no',
        public readonly string $discountPercent = '0',
        public readonly ?string $salespersonId = null,
        public readonly ?string $latitude = null,
        public readonly ?string $longitude = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateClientRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            documentType: strtoupper($request->string('document_type', 'V')->toString()),
            documentNumber: $request->string('document_number')->toString(),
            contacts: ClientContactData::collection($request->input('contacts', [])),
            addresses: ClientAddressData::collection($request->input('addresses', [])),
            clientTypeId: $request->input('client_type_id'),
            priceListId: $request->input('price_list_id'),
            legalName: $request->input('legal_name'),
            phone: $request->input('phone'),
            mobile: $request->input('mobile'),
            email: $request->input('email'),
            address: $request->input('address'),
            city: $request->input('city'),
            state: $request->input('state'),
            country: $request->input('country'),
            paymentTermDays: $request->integer('payment_term_days'),
            creditLimit: (string) $request->input('credit_limit', 0),
            creditBlocked: $request->string('credit_blocked', 'no')->toString(),
            discountPercent: (string) $request->input('discount_percent', 0),
            salespersonId: $request->input('salesperson_id'),
            latitude: $request->input('latitude'),
            longitude: $request->input('longitude'),
            notes: $request->input('notes'),
        );
    }
}
