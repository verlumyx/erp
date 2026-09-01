<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\EntryLineData;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Lo que la entrada no puede comprobar sin leer la orden de compra y el maestro
 * de artículos: que la orden sea del mismo proveedor, que no se reciba más de
 * lo que queda pendiente y que el lote y las series correspondan a artículos
 * que los admiten.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: la entrada se edita en borrador y cada guardado vuelve a comprobar lo
 * mismo.
 */
class EntryLimitsService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $orders,
        private readonly ItemRepositoryInterface $items,
        private readonly EntryRepositoryInterface $entries,
    ) {}

    /**
     * @param  array<int, EntryLineData>  $lines
     * @param  bool  $allowsOverReceipt  El usuario tiene el permiso que deja
     *                                   recibir más de lo pedido.
     * @param  string|null  $entryId  La entrada que se está guardando: sus
     *                                propias líneas ya guardadas no compiten
     *                                consigo misma.
     *
     * @throws ValidationException
     */
    public function guard(
        ?string $sourceableType,
        ?string $sourceableId,
        ?string $supplierId,
        string $warehouseId,
        string $entryType,
        ?string $companyId,
        array $lines,
        bool $allowsOverReceipt = false,
        ?string $entryId = null,
    ): void {
        $items = $this->itemsOf($companyId, $lines);

        $this->guardTraceability($lines, $items);
        $this->guardInitialInventory($entryType, $companyId, $warehouseId, $lines, $entryId);

        if (blank($sourceableId)) {
            $this->guardOrphanLines($lines);

            return;
        }

        $order = $this->guardSourceOrder($sourceableType, $sourceableId, $supplierId, $companyId);

        $this->guardReceivedQuantities($order, $lines, $allowsOverReceipt, $entryId);
    }

    /**
     * El documento origen no es una foreign key —es un par polimórfico—, así
     * que la base de datos no puede garantizar su integridad: se comprueba
     * aquí, antes de guardar. Debe existir, ser de la misma empresa y del mismo
     * proveedor que la entrada, y no estar anulado: una orden anulada no pidió
     * nada, así que no hay nada que recibir de ella.
     *
     * @throws ValidationException
     */
    private function guardSourceOrder(
        ?string $sourceableType,
        string $sourceableId,
        ?string $supplierId,
        ?string $companyId,
    ): PurchaseOrder {
        if (! in_array((string) $sourceableType, Entry::SOURCE_TYPES, true)) {
            throw ValidationException::withMessages([
                'sourceable_type' => 'El tipo de documento origen no está admitido.',
            ]);
        }

        if (blank($supplierId)) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'Una entrada sin proveedor no puede salir de una orden de compra.',
            ]);
        }

        $order = $this->orders->findById($sourceableId, $companyId);

        if (! $order instanceof PurchaseOrder) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'La orden de compra indicada no existe en esta empresa.',
            ]);
        }

        if ($order->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'La orden de compra pertenece a otro proveedor.',
            ]);
        }

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'sourceable_id' => 'No se puede recibir mercancía de una orden de compra anulada.',
            ]);
        }

        return $order;
    }

    /**
     * Sin orden en la cabecera ninguna línea puede apuntar a la línea de una:
     * el Request ya lo comprueba, y aquí se vuelve a cerrar la puerta para el
     * caso en que el comando no venga de un formulario.
     *
     * @param  array<int, EntryLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardOrphanLines(array $lines): void
    {
        foreach ($lines as $index => $line) {
            if (filled($line->sourceableId)) {
                throw ValidationException::withMessages([
                    "lines.{$index}.sourceable_id" => 'Elige la orden de compra de origen antes de recibir una de sus líneas.',
                ]);
            }
        }
    }

    /**
     * La cantidad recibida no puede superar lo que queda pendiente de la orden,
     * salvo que el usuario tenga el permiso que lo autoriza: un proveedor a
     * veces despacha de más y alguien tiene que poder aceptarlo.
     *
     * La comparación exige que la línea se reciba en la **misma unidad** en la
     * que se pidió. Recibir cajas contra una orden en unidades obligaría a
     * convertir el pendiente de la orden en cada comprobación, y el número que
     * el comprador ve en su orden dejaría de cuadrar con el que ve el
     * almacenista.
     *
     * @param  array<int, EntryLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardReceivedQuantities(
        PurchaseOrder $order,
        array $lines,
        bool $allowsOverReceipt,
        ?string $entryId,
    ): void {
        $orderLines = $order->lines->keyBy('id');
        $errors = [];
        $requested = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active' || blank($line->sourceableId)) {
                continue;
            }

            $orderLine = $orderLines->get($line->sourceableId);

            if (! $orderLine instanceof PurchaseOrderLine) {
                $errors["lines.{$index}.sourceable_id"] = 'Esa línea no pertenece a la orden elegida.';

                continue;
            }

            if ($orderLine->item_id !== $line->itemId) {
                $errors["lines.{$index}.item_id"] = 'El artículo no es el de la línea de la orden.';

                continue;
            }

            if ($orderLine->measurement_unit_id !== $line->measurementUnitId) {
                $errors["lines.{$index}.measurement_unit_id"] = 'Recibe la línea en la misma unidad en la que se pidió.';

                continue;
            }

            /** Varias líneas de la entrada pueden recibir la misma línea de la orden. */
            $requested[$line->sourceableId] ??= ['quantity' => 0.0, 'index' => $index];
            $requested[$line->sourceableId]['quantity'] += $line->receivedQuantity;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($requested === [] || $allowsOverReceipt) {
            return;
        }

        $received = $this->entries->receivedQuantities(array_keys($requested), $entryId);

        foreach ($requested as $orderLineId => $entry) {
            $ordered = (float) $orderLines->get($orderLineId)->quantity;
            $available = round($ordered - ($received[$orderLineId] ?? 0.0), 4);

            if (round($entry['quantity'], 4) > $available) {
                $errors["lines.{$entry['index']}.quantity"] = $available > 0
                    ? "De esa línea solo quedan {$available} por recibir."
                    : 'Esa línea de la orden ya se recibió por completo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * El inventario inicial es el saldo con el que la empresa arranca, así que
     * un artículo solo lo carga una vez por bodega: la segunda vez ya no es un
     * saldo inicial, es un ajuste.
     *
     * @param  array<int, EntryLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardInitialInventory(
        string $entryType,
        ?string $companyId,
        string $warehouseId,
        array $lines,
        ?string $entryId,
    ): void {
        if ($entryType !== Entry::INITIAL_TYPE) {
            return;
        }

        $itemIds = array_values(array_unique(array_map(
            static fn (EntryLineData $line): string => $line->itemId,
            array_filter($lines, static fn (EntryLineData $line): bool => $line->status === 'active'),
        )));

        $loaded = $this->entries->itemsWithInitialEntry($companyId, $warehouseId, $itemIds, $entryId);

        if ($loaded === []) {
            return;
        }

        $errors = [];

        foreach ($lines as $index => $line) {
            if ($line->status === 'active' && in_array($line->itemId, $loaded, true)) {
                $errors["lines.{$index}.item_id"] = 'Ese artículo ya tiene inventario inicial cargado en esa bodega.';
            }
        }

        throw ValidationException::withMessages($errors);
    }

    /**
     * El lote y las series solo tienen sentido en artículos que los llevan, y
     * un artículo serializado se recibe unidad por unidad: cada una es su
     * serie, así que hacen falta tantas como unidades base entren.
     *
     * @param  array<int, EntryLineData>  $lines
     * @param  array<string, Item>  $items
     *
     * @throws ValidationException
     */
    private function guardTraceability(array $lines, array $items): void
    {
        $errors = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $item = $items[$line->itemId] ?? null;

            if (! $item instanceof Item) {
                continue;
            }

            $tracksLots = in_array($item->type, ItemLot::TRACKABLE_ITEM_TYPES, true);

            if (! $tracksLots && (filled($line->lotNumber) || filled($line->lotId))) {
                $errors["lines.{$index}.lot_number"] = 'Ese artículo no se controla por lote.';
            }

            if (filled($line->expiresAt) && blank($line->lotNumber) && blank($line->lotId)) {
                $errors["lines.{$index}.expires_at"] = 'El vencimiento es del lote: indica cuál se recibe.';
            }

            $serialized = $item->type === ItemSerial::TRACKABLE_ITEM_TYPE;

            if (! $serialized) {
                if ($line->serialNumbers !== []) {
                    $errors["lines.{$index}.serial_numbers"] = 'Ese artículo no se controla por serie.';
                }

                continue;
            }

            $expected = round($line->receivedQuantity * $this->factorFor($item, $line->measurementUnitId), 4);

            if (count($line->serialNumbers) !== (int) $expected || $expected != (float) (int) $expected) {
                $errors["lines.{$index}.serial_numbers"] = "Ese artículo se controla por serie: indica una serie por cada unidad aceptada ({$expected}).";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Factor con el que la unidad de la línea se convierte a la unidad base del
     * artículo. Una unidad sin registrar cae en 1, que es lo que ya rechazó el
     * Request.
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
     * Artículos de las líneas, indexados por id y resueltos a través del
     * repositorio de artículos: este módulo nunca consulta las tablas del
     * módulo de inventario directamente.
     *
     * @param  array<int, EntryLineData>  $lines
     * @return array<string, Item>
     */
    private function itemsOf(?string $companyId, array $lines): array
    {
        $items = [];

        foreach (array_unique(array_map(static fn (EntryLineData $line): string => $line->itemId, $lines)) as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $items[$itemId] = $item;
            }
        }

        return $items;
    }
}
