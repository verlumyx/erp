<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

use App\Modules\Transfer\Requests\UpdateStatusTransferRequest;

class UpdateStatusTransferCommand
{
    public function __construct(
        public readonly string $status,
        /**
         * Quién despacha desde el origen. Solo cuenta al confirmar: es el
         * momento en que alguien saca la mercancía de la bodega.
         */
        public readonly ?string $sentBy = null,
    ) {}

    public static function fromRequest(UpdateStatusTransferRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            sentBy: $request->user()?->id,
        );
    }
}
