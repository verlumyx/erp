<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Commands;

use App\Modules\PurchaseCreditNote\Requests\UpdateStatusPurchaseCreditNoteRequest;

class UpdateStatusPurchaseCreditNoteCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusPurchaseCreditNoteRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
