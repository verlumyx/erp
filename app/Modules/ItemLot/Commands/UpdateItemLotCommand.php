<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Commands;

use App\Modules\ItemLot\Requests\UpdateItemLotRequest;

class UpdateItemLotCommand
{
    public function __construct(
        public readonly string $lotNumber,
        public readonly ?string $manufacturedAt = null,
        public readonly ?string $expiresAt = null,
        public readonly ?string $supplierId = null,
    ) {}

    public static function fromRequest(UpdateItemLotRequest $request): self
    {
        return new self(
            lotNumber: $request->string('lot_number')->toString(),
            manufacturedAt: $request->input('manufactured_at'),
            expiresAt: $request->input('expires_at'),
            supplierId: $request->input('supplier_id'),
        );
    }
}
