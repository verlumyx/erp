<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

use App\Modules\Transfer\Requests\RegisterTransferReceiptRequest;

/**
 * La llegada de la mercancía al destino: qué se recibió, cuándo y quién lo
 * recibió.
 *
 * Es un comando aparte del cambio de estado porque no mueve el documento por su
 * ciclo de vida: registra el hecho físico de la recepción, y de él salen la
 * entrada al destino y la diferencia que se perdió en tránsito.
 */
class RegisterTransferReceiptCommand
{
    /**
     * @param  array<int, TransferReceiptLineData>  $lines
     */
    public function __construct(
        public readonly array $lines = [],
        public readonly ?string $receivedDate = null,
        public readonly ?string $receivedBy = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(RegisterTransferReceiptRequest $request): self
    {
        return new self(
            lines: TransferReceiptLineData::collection($request->input('lines', [])),
            receivedDate: $request->input('received_date'),
            /** Quien registra la llegada es quien la recibe. */
            receivedBy: $request->user()?->id,
            notes: $request->input('notes'),
        );
    }
}
