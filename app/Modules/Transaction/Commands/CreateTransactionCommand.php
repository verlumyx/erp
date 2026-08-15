<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Commands;

class CreateTransactionCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $type,
        public readonly string $category,
        public readonly float $amount,
        public readonly string $date,
        public readonly string $paymentMethod,
        public readonly string $description,
        public readonly ?string $recordedBy,
        public readonly ?string $subcategory = null,
        public readonly ?string $relatedType = null,
        public readonly ?string $relatedId = null,
        public readonly string $currency = 'USD',
        public readonly ?string $reference = null,
        public readonly ?string $periodFrom = null,
        public readonly ?string $periodTo = null,
        public readonly ?string $notes = null,
        public readonly ?string $receiptUrl = null,
    ) {}
}
