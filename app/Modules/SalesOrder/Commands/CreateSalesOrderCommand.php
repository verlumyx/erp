<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Commands;

use App\Modules\SalesOrder\Requests\CreateSalesOrderRequest;

class CreateSalesOrderCommand
{
    /**
     * @param  array<int, SalesOrderLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $clientId,
        public readonly string $warehouseId,
        public readonly string $orderDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        public readonly ?string $clientAddressId = null,
        public readonly ?string $priceListId = null,
        public readonly ?string $salespersonId = null,
        public readonly ?string $expectedDate = null,
        public readonly ?string $clientReference = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly int $paymentTermDays = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateSalesOrderRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            clientId: $request->string('client_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            orderDate: $request->string('order_date')->toString(),
            createdBy: $request->user()->id,
            lines: SalesOrderLineData::collection($request->input('lines', [])),
            clientAddressId: $request->input('client_address_id'),
            priceListId: $request->input('price_list_id'),
            salespersonId: $request->input('salesperson_id'),
            expectedDate: $request->input('expected_date'),
            clientReference: $request->input('client_reference'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            paymentTermDays: $request->integer('payment_term_days'),
            notes: $request->input('notes'),
        );
    }
}
