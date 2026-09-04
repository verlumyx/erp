<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Client\Models\Client;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Validation\ValidationException;

/**
 * Lo que el despacho no puede comprobar sin leer el documento origen: que el
 * pedido sea del mismo cliente, que las líneas despachadas sean suyas y que no
 * se saque más de lo que se pidió.
 *
 * El origen no es un foreign key —es una relación polimórfica—, así que su
 * integridad no la garantiza la base de datos: la garantiza este servicio antes
 * de guardar, y el origen queda protegido por la política de no borrado.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar lo necesitan
 * igual: el despacho se edita en borrador y cada guardado vuelve a comprobar lo
 * mismo.
 */
class DispatchSourceService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $orders,
        private readonly TransferRepositoryInterface $transfers,
        private readonly DispatchRepositoryInterface $dispatches,
    ) {}

    /**
     * @param  array<int, DispatchLineData>  $lines
     * @param  string|null  $dispatchId  El despacho que se está guardando: sus
     *                                   propias líneas ya guardadas no compiten
     *                                   consigo mismo.
     *
     * @throws ValidationException
     */
    public function guard(
        ?string $sourceableType,
        ?string $sourceableId,
        string $recipientType,
        string $recipientId,
        ?string $companyId,
        array $lines,
        ?string $dispatchId = null,
    ): void {
        $this->guardTraceability($lines, $companyId);

        if (blank($sourceableId) || ! in_array($sourceableType, Dispatch::SOURCE_TYPES, true)) {
            $this->rejectOrphanLineSources($lines);

            return;
        }

        if ($sourceableType === Transfer::MORPH_ALIAS) {
            $this->guardTransfer((string) $sourceableId, $recipientType, $recipientId, $companyId, $lines, $dispatchId);

            return;
        }

        $order = $this->orders->findById($sourceableId, $companyId);

        if (! $order instanceof SalesOrder) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'El pedido de origen no existe en esta empresa.',
            ]);
        }

        if ($order->client_id !== $recipientId || $recipientType !== Client::MORPH_ALIAS) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'El pedido de origen es de otro cliente.',
            ]);
        }

        /** Un pedido anulado no compromete nada: no hay qué despachar de él. */
        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'sourceable_id' => 'No se puede despachar un pedido anulado.',
            ]);
        }

        $this->guardOrderLines($order, $lines, $dispatchId);
    }

    /**
     * Lo que el despacho no puede comprobar sin leer el traslado: que exista,
     * que siga vivo y que la mercancía vaya de verdad a la bodega de destino
     * que el traslado pidió. Un traslado no tiene cliente: su destinatario es
     * una bodega propia.
     *
     * @param  array<int, DispatchLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardTransfer(
        string $sourceableId,
        string $recipientType,
        string $recipientId,
        ?string $companyId,
        array $lines,
        ?string $dispatchId,
    ): void {
        $transfer = $this->transfers->findById($sourceableId, $companyId);

        if (! $transfer instanceof Transfer) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'El traslado de origen no existe en esta empresa.',
            ]);
        }

        if ($transfer->status === 'cancelled') {
            throw ValidationException::withMessages([
                'sourceable_id' => 'No se puede despachar un traslado anulado.',
            ]);
        }

        if ($recipientType !== Warehouse::MORPH_ALIAS || $recipientId !== $transfer->destination_warehouse_id) {
            throw ValidationException::withMessages([
                'recipient_id' => 'El despacho de un traslado va a la bodega de destino del traslado.',
            ]);
        }

        $this->guardTransferLines($transfer, $lines, $dispatchId);
    }

    /**
     * La cantidad despachada no puede superar la trasladada menos la ya
     * despachada por otros despachos, y la línea origen tiene que ser del
     * traslado elegido.
     *
     * @param  array<int, DispatchLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardTransferLines(Transfer $transfer, array $lines, ?string $dispatchId): void
    {
        $transferLines = TransferLine::query()
            ->where('transfer_id', $transfer->id)
            ->get()
            ->keyBy('id');

        $errors = [];
        $requested = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active' || blank($line->sourceableId)) {
                continue;
            }

            $transferLine = $transferLines->get($line->sourceableId);

            if (! $transferLine instanceof TransferLine) {
                $errors["lines.{$index}.sourceable_id"] = 'Esa línea no pertenece al traslado de origen.';

                continue;
            }

            if ($transferLine->measurement_unit_id !== $line->measurementUnitId) {
                $errors["lines.{$index}.measurement_unit_id"] = 'La unidad debe ser la misma que la de la línea del traslado.';
            }

            if ($transferLine->item_id !== $line->itemId) {
                $errors["lines.{$index}.item_id"] = 'El artículo debe ser el mismo que el de la línea del traslado.';
            }

            $requested[$line->sourceableId] ??= ['quantity' => 0.0, 'index' => $index];
            $requested[$line->sourceableId]['quantity'] += $line->quantity;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($requested === []) {
            return;
        }

        $dispatched = $this->dispatches->dispatchedQuantities(array_keys($requested), $dispatchId);

        foreach ($requested as $transferLineId => $entry) {
            $ordered = (float) $transferLines->get($transferLineId)->quantity;
            $available = round($ordered - ($dispatched[$transferLineId] ?? 0.0), 4);

            if (round($entry['quantity'], 4) > $available) {
                $errors["lines.{$entry['index']}.quantity"] = $available > 0
                    ? "De esa línea solo quedan {$available} por despachar."
                    : 'Esa línea del traslado ya se despachó por completo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * La cantidad despachada no puede superar la pedida menos la ya despachada
     * por otros despachos, y la línea origen tiene que ser del pedido elegido.
     *
     * @param  array<int, DispatchLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardOrderLines(SalesOrder $order, array $lines, ?string $dispatchId): void
    {
        $orderLines = SalesOrderLine::query()
            ->where('sales_order_id', $order->id)
            ->get()
            ->keyBy('id');

        $errors = [];
        $requested = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active' || blank($line->sourceableId)) {
                continue;
            }

            $orderLine = $orderLines->get($line->sourceableId);

            if (! $orderLine instanceof SalesOrderLine) {
                $errors["lines.{$index}.sourceable_id"] = 'Esa línea no pertenece al pedido de origen.';

                continue;
            }

            if ($orderLine->measurement_unit_id !== $line->measurementUnitId) {
                $errors["lines.{$index}.measurement_unit_id"] = 'La unidad debe ser la misma que la de la línea del pedido.';
            }

            if ($orderLine->item_id !== $line->itemId) {
                $errors["lines.{$index}.item_id"] = 'El artículo debe ser el mismo que el de la línea del pedido.';
            }

            /** Varias líneas del despacho pueden salir de la misma línea del pedido. */
            $requested[$line->sourceableId] ??= ['quantity' => 0.0, 'index' => $index];
            $requested[$line->sourceableId]['quantity'] += $line->quantity;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($requested === []) {
            return;
        }

        $dispatched = $this->dispatches->dispatchedQuantities(array_keys($requested), $dispatchId);

        foreach ($requested as $orderLineId => $entry) {
            $ordered = (float) $orderLines->get($orderLineId)->quantity;
            $available = round($ordered - ($dispatched[$orderLineId] ?? 0.0), 4);

            if (round($entry['quantity'], 4) > $available) {
                $errors["lines.{$entry['index']}.quantity"] = $available > 0
                    ? "De esa línea solo quedan {$available} por despachar."
                    : 'Esa línea del pedido ya se despachó por completo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Una línea no puede venir de un pedido si el despacho no viene de ninguno.
     *
     * @param  array<int, DispatchLineData>  $lines
     *
     * @throws ValidationException
     */
    private function rejectOrphanLineSources(array $lines): void
    {
        $errors = [];

        foreach ($lines as $index => $line) {
            if (filled($line->sourceableId)) {
                $errors["lines.{$index}.sourceable_id"] = 'El despacho no tiene documento origen: sus líneas tampoco pueden tenerlo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Lo que la trazabilidad exige del maestro de artículos: que el lote y la
     * serie sean de ese artículo, que solo se pidan a quien los lleva, y que un
     * artículo serializado saque tantas series como unidades base salen.
     *
     * Las series ya no obligan a partir la línea en una por unidad: viven en su
     * propia tabla, así que una línea de cinco laptops lleva sus cinco series.
     *
     * @param  array<int, DispatchLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardTraceability(array $lines, ?string $companyId): void
    {
        $errors = [];

        $items = Item::query()
            ->with('units')
            ->whereIn('id', array_values(array_unique(array_map(
                static fn (DispatchLineData $line): string => $line->itemId,
                $lines,
            ))))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->keyBy('id');

        $lotItems = ItemLot::query()
            ->whereIn('id', $this->lotIdsOf($lines))
            ->pluck('item_id', 'id')
            ->all();

        $serialItems = ItemSerial::query()
            ->whereIn('id', $this->serialIdsOf($lines))
            ->pluck('item_id', 'id')
            ->all();

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $item = $items->get($line->itemId);

            if (! $item instanceof Item) {
                continue;
            }

            $lots = $line->activeLots();
            $serials = $line->activeSerials();

            if (! $item->movesStock() && $lots !== []) {
                $errors["lines.{$index}.lots"] = 'Ese artículo no se controla por lote.';
            }

            foreach ($lots as $lot) {
                if (($lotItems[$lot->lotId] ?? null) !== $line->itemId) {
                    $errors["lines.{$index}.lots"] = 'Alguno de los lotes no es de ese artículo.';

                    break;
                }
            }

            $serialized = $item->type === ItemSerial::TRACKABLE_ITEM_TYPE;

            if (! $serialized) {
                if ($serials !== []) {
                    $errors["lines.{$index}.serials"] = 'Ese artículo no se controla por serie.';
                }

                continue;
            }

            foreach ($serials as $serial) {
                if (($serialItems[$serial->serialId] ?? null) !== $line->itemId) {
                    $errors["lines.{$index}.serials"] = 'Alguna de las series no es de ese artículo.';

                    break;
                }
            }

            $expected = round($line->quantity * $this->factorFor($item, $line->measurementUnitId), 4);

            if (count($serials) !== (int) $expected || $expected != (float) (int) $expected) {
                $errors["lines.{$index}.serials"] = "Ese artículo se controla por serie: indica una serie por cada unidad que sale ({$expected}).";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, DispatchLineData>  $lines
     * @return array<int, string>
     */
    private function lotIdsOf(array $lines): array
    {
        $ids = [];

        foreach ($lines as $line) {
            foreach ($line->activeLots() as $lot) {
                $ids[] = $lot->lotId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, DispatchLineData>  $lines
     * @return array<int, string>
     */
    private function serialIdsOf(array $lines): array
    {
        $ids = [];

        foreach ($lines as $line) {
            foreach ($line->activeSerials() as $serial) {
                $ids[] = $serial->serialId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Factor con el que la unidad de la línea se convierte a la unidad base.
     * Una unidad sin registrar cae en 1, que es lo que ya rechazó el Request.
     */
    private function factorFor(Item $item, string $measurementUnitId): float
    {
        foreach ($item->units as $unit) {
            /** @var ItemUnit $unit */
            if ($unit->measurement_unit_id === $measurementUnitId) {
                return (float) $unit->conversion_factor;
            }
        }

        return 1.0;
    }

    /**
     * Alias del morph map del documento origen, ya normalizado. Un despacho sin
     * origen los deja los dos vacíos.
     *
     * @return array{0: string|null, 1: string|null}
     */
    public static function normalize(?string $type, ?string $id): array
    {
        if (blank($id) || ! in_array($type, Dispatch::SOURCE_TYPES, true)) {
            return [null, null];
        }

        return [$type, $id];
    }
}
