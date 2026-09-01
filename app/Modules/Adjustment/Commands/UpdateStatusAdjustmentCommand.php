<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Commands;

use App\Modules\Adjustment\Requests\UpdateStatusAdjustmentRequest;

class UpdateStatusAdjustmentCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
        /**
         * Quién firma el ajuste. Se resuelve al armar el comando, que es donde
         * vive lo que sabe el request: el servicio no consulta la sesión.
         */
        public readonly ?string $approvedBy = null,
    ) {}

    public static function fromRequest(UpdateStatusAdjustmentRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
            approvedBy: $request->user()?->id,
        );
    }
}
