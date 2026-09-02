<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\DispatchDeliveryLineData;
use App\Modules\Dispatch\Commands\RegisterDispatchDeliveryCommand;
use App\Modules\Dispatch\Commands\WriteDispatchDeliveryCommand;
use App\Modules\Dispatch\Exceptions\DispatchNotFoundException;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Dispatch\Models\DispatchLineLot;
use App\Modules\Dispatch\Models\DispatchLineSerial;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El final del viaje: qué recibió el cliente y qué volvió a la bodega.
 *
 * Es un eje distinto del ciclo de vida del documento. Confirmar el despacho
 * saca la mercancía; registrar la entrega dice cuánta de ella se quedó el
 * cliente. Lo que no se quedó **reingresa con un movimiento `in`** al costo con
 * el que salió —el congelado en la línea—, porque entregar de menos no puede
 * inventar ni destruir margen, y el pedido recupera ese cupo.
 *
 * Solo se registra una vez y solo sobre un despacho confirmado: en borrador la
 * mercancía no ha salido, y anulado ya volvió entera.
 */
class DispatchDeliveryService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly DispatchPostingService $posting,
    ) {}

    public function execute(
        string $id,
        RegisterDispatchDeliveryCommand $command,
        ?string $companyId = null,
    ): Dispatch {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new DispatchNotFoundException;
        }

        $this->guardState($model);

        $lines = $this->repository->activeLines($model);
        $quantities = $this->resolveQuantities($lines, $command);

        $this->guardDeliveryStatus($model, $lines, $quantities, $command);

        DB::transaction(function () use ($model, $lines, $quantities, $command): void {
            $this->repository->writeDelivery($model, new WriteDispatchDeliveryCommand(
                deliveryStatus: $command->deliveryStatus,
                deliveryDate: $command->deliveryDate ?? now()->toDateString(),
                lines: $quantities,
                receivedByName: $command->receivedByName,
                receivedByDocument: $command->receivedByDocument,
                signaturePath: $command->signaturePath,
                evidencePath: $command->evidencePath,
                latitude: $command->latitude,
                longitude: $command->longitude,
                rejectionReason: $command->rejectionReason,
            ));

            foreach ($lines as $line) {
                $back = round((float) $line->quantity - $quantities[$line->id]['delivered'], 4);

                if ($back <= 0) {
                    continue;
                }

                $this->registerEntry($model, $line, $back);
                $this->posting->moveOrderLine($model, $line, -$back);
            }
        });

        return $this->repository->findOrFail($id, $companyId);
    }

    /**
     * La entrega se registra sobre mercancía que ya salió, y una sola vez.
     *
     * @throws ValidationException
     */
    private function guardState(Dispatch $model): void
    {
        if ($model->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'delivery_status' => 'Solo se registra la entrega de un despacho confirmado.',
            ]);
        }

        if ($model->isDeliverySettled()) {
            throw ValidationException::withMessages([
                'delivery_status' => 'La entrega de este despacho ya se registró.',
            ]);
        }
    }

    /**
     * Lo entregado y lo devuelto de cada línea, ya resueltos.
     *
     * Una línea que no viene en el request se da por entregada entera: lo
     * normal es que el viaje salga bien y la pantalla solo mande lo que falló.
     *
     * @param  array<int, DispatchLine>  $lines
     * @return array<string, array{delivered: float, returned: float}>
     *
     * @throws ValidationException
     */
    private function resolveQuantities(array $lines, RegisterDispatchDeliveryCommand $command): array
    {
        $sent = [];

        foreach ($command->lines as $line) {
            $sent[$line->id] = $line;
        }

        $errors = [];
        $quantities = [];

        foreach ($lines as $index => $line) {
            $shipped = round((float) $line->quantity, 4);
            $entry = $sent[$line->id] ?? null;

            $delivered = $entry instanceof DispatchDeliveryLineData
                ? $entry->deliveredQuantity
                : $shipped;

            if ($delivered < 0 || $delivered > $shipped) {
                $errors["lines.{$index}.delivered_quantity"] = "De esa línea salieron {$shipped}: no se puede entregar más.";

                continue;
            }

            $back = round($shipped - $delivered, 4);
            $returned = $entry?->returnedQuantity ?? $back;

            if ($returned < 0 || $returned > $back) {
                $errors["lines.{$index}.returned_quantity"] = 'El cliente no puede devolver más de lo que no se quedó.';

                continue;
            }

            $quantities[$line->id] = ['delivered' => $delivered, 'returned' => $returned];
        }

        /** Un id que no es de este despacho es un error de la pantalla, no del usuario. */
        $unknown = array_diff(array_keys($sent), array_keys($quantities));

        if ($unknown !== []) {
            $errors['lines'] = 'Alguna de las líneas enviadas no es de este despacho.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $quantities;
    }

    /**
     * El resultado declarado tiene que coincidir con lo que dicen las
     * cantidades: no se cierra como entregado un viaje que volvió a medias.
     *
     * @param  array<int, DispatchLine>  $lines
     * @param  array<string, array{delivered: float, returned: float}>  $quantities
     *
     * @throws ValidationException
     */
    private function guardDeliveryStatus(
        Dispatch $model,
        array $lines,
        array $quantities,
        RegisterDispatchDeliveryCommand $command,
    ): void {
        $shipped = 0.0;
        $delivered = 0.0;

        foreach ($lines as $line) {
            $shipped += round((float) $line->quantity, 4);
            $delivered += $quantities[$line->id]['delivered'];
        }

        $expected = match (true) {
            $delivered <= 0 => Dispatch::REFUSED_DELIVERY_STATUSES,
            round($delivered, 4) >= round($shipped, 4) => ['delivered'],
            default => ['partial_delivered'],
        };

        if (! in_array($command->deliveryStatus, $expected, true)) {
            throw ValidationException::withMessages([
                'delivery_status' => 'El resultado de la entrega no coincide con las cantidades recibidas.',
            ]);
        }

        if ($command->deliveryStatus === 'rejected' && blank($command->rejectionReason)) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Explica por qué el cliente rechazó la entrega.',
            ]);
        }
    }

    /**
     * Lo que vuelve entra otra vez a la bodega de origen, a la misma ubicación
     * de la que salió y al costo con el que salió.
     */
    private function registerEntry(Dispatch $dispatch, DispatchLine $line, float $quantity): void
    {
        /** El reingreso se mide en la misma proporción que la salida. */
        $factor = (float) $line->quantity > 0
            ? (float) $line->base_quantity / (float) $line->quantity
            : 1.0;

        try {
            foreach ($this->returnPlan($line, round($quantity * $factor, 4)) as $back) {
                $this->movements->execute(new RegisterInventoryMovementCommand(
                    companyId: (string) $dispatch->company_id,
                    itemId: $line->item_id,
                    warehouseId: $dispatch->warehouse_id,
                    locationId: $this->posting->locationFor($dispatch, $line),
                    type: 'in',
                    originType: Dispatch::MOVEMENT_ORIGIN_TYPE,
                    originId: $dispatch->id,
                    quantity: $back['quantity'],
                    /** Vuelve al costo con el que salió, no al promedio de hoy. */
                    unitCost: round((float) $line->unit_cost, 6),
                    movementDate: $dispatch->delivery_date?->toDateString(),
                    originLineId: $line->id,
                    lotId: $back['lotId'],
                    serialId: $back['serialId'],
                    notes: "Reingreso por entrega del despacho {$dispatch->code}.",
                    createdBy: $dispatch->created_by,
                ));
            }
        } catch (NonInventoriedItemException) {
            // Un artículo sin existencia no salió del kardex: tampoco vuelve.
        }
    }

    /**
     * Qué vuelve de cada lote y de qué serie, en unidad base.
     *
     * De una línea serializada vuelven las **últimas** series: el cliente se
     * quedó con las primeras que le entregaron, y hace falta un criterio fijo
     * para que dos entregas iguales devuelvan lo mismo. De una línea con lotes
     * vuelve lo suyo a cada uno, en proporción a lo que salió, con el último
     * absorbiendo el redondeo para que la suma cuadre.
     *
     * @return array<int, array{lotId: ?string, serialId: ?string, quantity: float}>
     */
    private function returnPlan(DispatchLine $line, float $base): array
    {
        if ($base <= 0.0) {
            return [];
        }

        $serials = $line->serials->where('status', 'active')->values();

        if ($serials->isNotEmpty()) {
            return $serials
                ->slice(max($serials->count() - (int) round($base), 0))
                ->map(static fn (DispatchLineSerial $serial): array => [
                    'lotId' => $serial->dispatchLineLot?->lot_id,
                    'serialId' => $serial->serial_id,
                    'quantity' => 1.0,
                ])
                ->values()
                ->all();
        }

        $lots = $line->lots->where('status', 'active')->values();

        if ($lots->isEmpty()) {
            return [['lotId' => null, 'serialId' => null, 'quantity' => $base]];
        }

        $total = round($lots->sum(static fn (DispatchLineLot $lot): float => (float) $lot->base_quantity), 4);

        if ($total <= 0.0) {
            return [['lotId' => null, 'serialId' => null, 'quantity' => $base]];
        }

        $plan = [];
        $assigned = 0.0;
        $last = $lots->count() - 1;

        foreach ($lots as $position => $lot) {
            /** @var DispatchLineLot $lot */
            $back = $position === $last
                ? round($base - $assigned, 4)
                : round((float) $lot->base_quantity * $base / $total, 4);

            $assigned = round($assigned + $back, 4);

            if ($back <= 0.0) {
                continue;
            }

            $plan[] = ['lotId' => $lot->lot_id, 'serialId' => null, 'quantity' => $back];
        }

        return $plan;
    }
}
