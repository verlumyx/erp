<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Commands;

use App\Modules\Supplier\Requests\CreateSupplierRequest;

class CreateSupplierCommand
{
    /**
     * @param  array<int, SupplierContactData>  $contacts
     * @param  array<int, SupplierAddressData>  $addresses
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $documentType,
        public readonly string $documentNumber,
        public readonly string $createdBy,
        public readonly array $contacts = [],
        public readonly array $addresses = [],
        public readonly ?string $supplierTypeId = null,
        public readonly ?string $legalName = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $mobile = null,
        public readonly ?string $website = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $country = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        public readonly int $paymentTermDays = 0,
        public readonly string $creditLimit = '0',
        public readonly int $leadTimeDays = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateSupplierRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            documentType: strtoupper($request->string('document_type', 'J')->toString()),
            documentNumber: $request->string('document_number')->toString(),
            createdBy: $request->user()->id,
            contacts: SupplierContactData::collection($request->input('contacts', [])),
            addresses: SupplierAddressData::collection($request->input('addresses', [])),
            supplierTypeId: $request->input('supplier_type_id'),
            legalName: $request->input('legal_name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            mobile: $request->input('mobile'),
            website: $request->input('website'),
            address: $request->input('address'),
            city: $request->input('city'),
            state: $request->input('state'),
            country: $request->input('country'),
            currency: strtoupper($request->string('currency')->toString()),
            paymentTermDays: $request->integer('payment_term_days'),
            creditLimit: (string) $request->input('credit_limit', 0),
            leadTimeDays: $request->integer('lead_time_days'),
            notes: $request->input('notes'),
        );
    }
}
