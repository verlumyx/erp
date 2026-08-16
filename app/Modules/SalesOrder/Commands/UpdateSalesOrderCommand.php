<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Commands;

use App\Modules\SalesOrder\Requests\UpdateSalesOrderRequest;

class UpdateSalesOrderCommand
{
    /**
     * @param  array<int, SalesOrderLineData>  $lines
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $warehouseId,
        public readonly string $orderDate,
        public readonly array $lines = [],
        public readonly ?string $clientAddressId = null,
        public readonly ?string $priceListId = null,
        public readonly ?string $salespersonId = null,
        public readonly ?string $expectedDate = null,
        public readonly ?string $clientReference = null,
        public readonly string $currency = 'USD',
        public readonly string $exchangeRate = '1',
        public readonly int $paymentTermDays = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateSalesOrderRequest $request): self
    {
        return new self(
            clientId: $request->string('client_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            orderDate: $request->string('order_date')->toString(),
            lines: SalesOrderLineData::collection($request->input('lines', [])),
            clientAddressId: $request->input('client_address_id'),
            priceListId: $request->input('price_list_id'),
            salespersonId: $request->input('salesperson_id'),
            expectedDate: $request->input('expected_date'),
            clientReference: $request->input('client_reference'),
            currency: strtoupper($request->string('currency', 'USD')->toString()),
            exchangeRate: (string) $request->input('exchange_rate', 1),
            paymentTermDays: $request->integer('payment_term_days'),
            notes: $request->input('notes'),
        );
    }
}
