<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

use App\Modules\Pos\Requests\CreatePosRegisterRequest;

class CreatePosRegisterCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $warehouseId,
        public readonly string $defaultClientId,
        public readonly string $createdBy,
        public readonly ?string $priceListId = null,
        public readonly ?string $receiptPrinterName = null,
        public readonly ?string $invoiceSeries = null,
        public readonly string $allowPriceOverride = 'no',
        public readonly string $allowCustomDiscounts = 'no',
        public readonly float $maxDiscountPercent = 0.0,
        public readonly ?string $notes = null,
        public readonly string $status = 'active',
    ) {}

    public static function fromRequest(CreatePosRegisterRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->trim()->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            defaultClientId: $request->string('default_client_id')->toString(),
            createdBy: $request->user()->id,
            priceListId: $request->input('price_list_id') ?: null,
            receiptPrinterName: $request->input('receipt_printer_name') ?: null,
            invoiceSeries: $request->input('invoice_series') ?: null,
            allowPriceOverride: $request->input('allow_price_override', 'no'),
            allowCustomDiscounts: $request->input('allow_custom_discounts', 'no'),
            maxDiscountPercent: (float) $request->input('max_discount_percent', 0),
            notes: $request->input('notes') ?: null,
            status: $request->input('status', 'active'),
        );
    }
}
