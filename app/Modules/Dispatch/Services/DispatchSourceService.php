<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Item\Models\Item;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
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
        string $clientId,
        ?string $companyId,
        array $lines,
        ?string $dispatchId = null,
    ): void {
        $this->guardSerials($lines, $companyId);

        if (blank($sourceableId) || $sourceableType !== SalesOrder::MORPH_ALIAS) {
            $this->rejectOrphanLineSources($lines);

            return;
        }

        $order = $this->orders->findById($sourceableId, $companyId);

        if (! $order instanceof SalesOrder) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'El pedido de origen no existe en esta empresa.',
            ]);
        }

        if ($order->client_id !== $clientId) {
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
     * La serie que sale tiene que ser una del artículo de la línea. Un artículo
     * serializado no se despacha a granel: cada unidad es su serie, así que la
     * línea que lo mueve saca exactamente una.
     *
     * @param  array<int, DispatchLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardSerials(array $lines, ?string $companyId): void
    {
        $errors = [];

        $itemTypes = Item::query()
            ->whereIn('id', array_values(array_unique(array_map(
                static fn (DispatchLineData $line): string => $line->itemId,
                $lines,
            ))))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->pluck('type', 'id')
            ->all();

        $serialItems = ItemSerial::query()
            ->whereIn('id', array_values(array_filter(array_map(
                static fn (DispatchLineData $line): ?string => $line->serialId,
                $lines,
            ))))
            ->pluck('item_id', 'id')
            ->all();

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $serialized = ($itemTypes[$line->itemId] ?? null) === ItemSerial::TRACKABLE_ITEM_TYPE;

            if ($serialized && blank($line->serialId)) {
                $errors["lines.{$index}.serial_id"] = 'Ese artículo se controla por serie: indica la que sale.';

                continue;
            }

            if (blank($line->serialId)) {
                continue;
            }

            if (($serialItems[$line->serialId] ?? null) !== $line->itemId) {
                $errors["lines.{$index}.serial_id"] = 'La serie indicada no es de ese artículo.';

                continue;
            }

            /** Una serie identifica una unidad: no se despachan dos con la misma. */
            if (round($line->quantity, 4) !== 1.0) {
                $errors["lines.{$index}.quantity"] = 'Una serie despacha exactamente una unidad.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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
