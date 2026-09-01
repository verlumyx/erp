<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Commands;

use App\Modules\ClientCollection\Requests\UpdateCheckStatusClientCollectionRequest;

/**
 * El cheque avanza por su propio carril —depositado, conformado, devuelto— sin
 * mover el estado del cobro, salvo cuando rebota: ahí el dinero nunca entró.
 */
class UpdateCheckStatusClientCollectionCommand
{
    public function __construct(
        public readonly string $checkStatus,
    ) {}

    public static function fromRequest(UpdateCheckStatusClientCollectionRequest $request): self
    {
        return new self(
            checkStatus: $request->string('check_status')->toString(),
        );
    }
}
