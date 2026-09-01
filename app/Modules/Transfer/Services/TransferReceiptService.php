<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Commands\RegisterTransferReceiptCommand;
use App\Modules\Transfer\Commands\TransferReceiptLineData;
use App\Modules\Transfer\Commands\WriteTransferReceiptCommand;
use App\Modules\Transfer\Exceptions\TransferNotFoundException;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El final del viaje: qué llegó a la bodega de destino.
 *
 * Es el segundo paso de un traslado que no es inmediato. Confirmarlo sacó la
 * mercancía del origen y la dejó en la bodega de tránsito; recibirla la saca de
 * ahí y la mete en el destino, **al costo con el que salió**, que es el
 * congelado en la línea.
 *
 * Lo que no llegó no se inventa ni se pierde: se queda en la bodega de tránsito
 * como saldo vivo, y ahí es donde un Ajuste tendrá que justificarlo. Por eso la
 * diferencia impide cerrar el traslado mientras nadie la explique.
 *
 * Solo se registra una vez y solo sobre un traslado confirmado: en borrador la
 * mercancía no ha salido, y anulado ya volvió entera. Un traslado inmediato —sin
 * bodega de tránsito— no tiene recepción que registrar: llegó al confirmarse.
 */
class TransferReceiptService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
        private readonly TransferPostingService $posting,
    ) {}

    public function execute(
        string $id,
        RegisterTransferReceiptCommand $command,
        ?string $companyId = null,
    ): Transfer {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new TransferNotFoundException;
        }

        $this->guardState($model);

        $lines = $this->repository->activeLines($model);
        $quantities = $this->resolveQuantities($lines, $command);

        $receivedDate = $command->receivedDate ?? now()->toDateString();

        DB::transaction(function () use ($model, $lines, $quantities, $command, $receivedDate): void {
            $missing = false;

            foreach ($lines as $line) {
                $received = $quantities[$line->id]['received'];
                $missing = $missing || $quantities[$line->id]['difference'] > 0;

                /** El kardex mide en unidad base; la recepción se captura en la de la línea. */
                $base = round($received * $line->baseFactor(), 4);
                $unitCost = (float) $line->unit_cost;

                $this->posting->registerTransitExit($model, $line, $base, $unitCost, $receivedDate);

                $this->posting->registerArrival(
                    transfer: $model,
                    line: $line,
                    warehouseId: $model->destination_warehouse_id,
                    locationId: $line->destination_location_id,
                    quantity: $base,
                    unitCost: $unitCost,
                    movementDate: $receivedDate,
                );
            }

            $this->repository->writeReceipt($model, new WriteTransferReceiptCommand(
                transferStatus: $missing ? 'partial_received' : 'received',
                /** Lo que faltó deja el documento a medias hasta que se justifique. */
                status: $missing ? 'partial' : $model->status,
                receivedDate: $receivedDate,
                lines: $quantities,
                receivedBy: $command->receivedBy,
                notes: $command->notes,
            ));
        });

        return $this->repository->findOrFail($id, $companyId);
    }

    /**
     * La recepción se registra sobre mercancía que ya salió y que todavía
     * viaja, y una sola vez.
     *
     * @throws ValidationException
     */
    private function guardState(Transfer $model): void
    {
        if (! $model->isTwoStep()) {
            throw ValidationException::withMessages([
                'lines' => 'Este traslado es inmediato: la mercancía llegó al destino al confirmarlo.',
            ]);
        }

        if ($model->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'lines' => 'Solo se registra la recepción de un traslado confirmado.',
            ]);
        }

        if ($model->isReceiptSettled()) {
            throw ValidationException::withMessages([
                'lines' => 'La recepción de este traslado ya se registró.',
            ]);
        }
    }

    /**
     * Lo recibido y lo que faltó de cada línea, ya resueltos y en la unidad de
     * la línea.
     *
     * Una línea que no viene en el request se da por recibida entera: lo normal
     * es que el viaje salga bien y la pantalla solo mande lo que falló.
     *
     * @param  array<int, TransferLine>  $lines
     * @return array<string, array{received: float, difference: float}>
     *
     * @throws ValidationException
     */
    private function resolveQuantities(array $lines, RegisterTransferReceiptCommand $command): array
    {
        $sent = [];

        foreach ($command->lines as $line) {
            $sent[$line->id] = $line;
        }

        $errors = [];
        $quantities = [];

        foreach ($lines as $index => $line) {
            $shipped = round((float) $line->sent_quantity, 4);
            $entry = $sent[$line->id] ?? null;

            $received = $entry instanceof TransferReceiptLineData
                ? $entry->receivedQuantity
                : $shipped;

            /**
             * Un traslado no crea mercancía: recibir más de lo que salió sería
             * un hallazgo de inventario, y eso lo registra un Ajuste.
             */
            if ($received < 0 || $received > $shipped) {
                $errors["lines.{$index}.received_quantity"] = "De esa línea salieron {$shipped}: no puede llegar más.";

                continue;
            }

            $quantities[$line->id] = [
                'received' => $received,
                'difference' => round($shipped - $received, 4),
            ];
        }

        /** Un id que no es de este traslado es un error de la pantalla, no del usuario. */
        $unknown = array_diff(array_keys($sent), array_keys($quantities));

        if ($unknown !== []) {
            $errors['lines'] = 'Alguna de las líneas enviadas no es de este traslado.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $quantities;
    }
}
