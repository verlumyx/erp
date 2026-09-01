<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Commands;

use App\Modules\SalesCreditNote\Requests\UpdateStatusSalesCreditNoteRequest;

class UpdateStatusSalesCreditNoteCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusSalesCreditNoteRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
