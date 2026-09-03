<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Commands\EntryLineData;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Entry\Services\EntryPricingService;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Item\Models\Item;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * La entrada espejo de la orden de compra: el documento que sí recibe la
 * mercancía.

 * Aprobar la orden no mete nada en la bodega —solo la anuncia como mercancía en
 * camino—, pero deja escrito lo que hay que recibir: una entrada `ENT` en
 * borrador, ya colgada de la orden y con las líneas pendientes copiadas. Bodega
 * abre ese borrador, completa lo físico —ubicación, lotes, series, lo que de
 * verdad llegó— y lo confirma. Es ese confirmar, y no el de la orden, el que
 * toca el kardex.
 *
 * Un artículo sin existencia —un servicio, un flete contratado en la misma
 * orden— no viaja a la entrada: no hay nada que recibir en una bodega.
 */
class PurchaseOrderMirrorEntryService
{

    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $orders,
        private readonly EntryRepositoryInterface $entries,
        private readonly EntryPricingService $pricing,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Crea la entrada que recibirá la mercancía de la orden.
     *
     * Devuelve `null` cuando no hay nada que recibir: una orden solo de
     * servicios, una ya recibida por completo, o una que ya tiene su entrada.
     * Que alguien la hubiera armado a mano antes de aprobar no es razón para
     * tumbar la aprobación: se respeta la que ya existe.
     */
    public function create(PurchaseOrder $order): ?Entry
    {
        if ($this->liveEntry($order) instanceof Entry) {
            return null;
        }

        $lines = $this->pendingLines($order);

        if ($lines === []) {
            return null;
        }

        $id = (string) Str::uuid7();
        $entryDate = now()->toDateString();

        $this->entries->create(
            new CreateEntryCommand(
                id: $id,
                companyId: (string) $order->company_id,
                warehouseId: (string) $order->warehouse_id,
                entryDate: $entryDate,
                createdBy: (string) $order->created_by,
                lines: $lines,
                supplierId: $order->supplier_id,
                sourceableType: PurchaseOrder::MORPH_ALIAS,
                sourceableId: $order->id,
                entryType: Entry::SUPPLIER_TYPE,
                currency: $order->currency,
                notes: "Generada al aprobar la orden {$order->code}.",
            ),
            /**
             * La entrada se valora con la tasa de **su** fecha, no con la de la
             * orden: son dos documentos y cada uno congela la suya
             * (`docs/logistica.md` §3).
             */
            $this->rates->forDocument((string) $order->company_id, $order->currency, $entryDate),
            /** El costo no lo decide nadie aquí: sale de la línea de la orden. */
            $this->pricing->apply((string) $order->company_id, PurchaseOrder::MORPH_ALIAS, $order->id, $lines),
        );

        return $this->entries->findOrFail($id, $order->company_id);
    }

    /**
     * Anula la entrada en borrador de la orden, si la hay. Lo llama la orden al
     * anularse: lo que ya no se va a comprar tampoco se va a recibir.
     */
    public function cancel(PurchaseOrder $order): void
    {
        $entry = $this->liveEntry($order);

        if (! $entry instanceof Entry || $entry->status !== 'draft') {
            return;
        }

        $this->entries->updateStatus($entry, new UpdateStatusEntryCommand(status: 'cancelled'));
    }

    /**
     * Una orden con mercancía ya recibida no se anula por las buenas: primero
     * hay que anular la entrada que la metió, que es la que sabe deshacer su
     * propio asiento en el kardex (`docs/compras.md` §2.2).
     *
     * @throws ValidationException
     */
    public function guardCancellable(PurchaseOrder $order): void
    {
        $entry = $this->liveEntry($order);

        if (! $entry instanceof Entry || ! in_array($entry->status, Entry::POSTED_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "La orden ya tiene la entrada {$entry->code} confirmada: anúlala primero.",
        ]);
    }

    /**
     * La entrada de la orden que todavía cuenta. Una anulada no estorba: la
     * orden puede volver a generar la suya.
     */
    public function liveEntry(PurchaseOrder $order): ?Entry
    {
        $result = $this->entries->search(new SearchEntryCommand(
            filters: ['sourceable_id' => $order->id],
            limit: 50,
            companyId: $order->company_id,
        ));

        foreach ($result['data'] as $entry) {
            if ($entry->status !== 'cancelled') {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Lo que la orden todavía espera recibir, como líneas de la entrada.
     *
     * El dinero va en cero a propósito: lo escribe `EntryPricingService`
     * copiándolo de la línea de la orden. Y la unidad se copia tal cual, porque
     * una entrada recibe en la misma unidad en la que se pidió.
     *
     * @return array<int, EntryLineData>
     */
    private function pendingLines(PurchaseOrder $order): array
    {
        $lines = [];

        foreach ($this->orders->activeLines($order) as $line) {
            $pending = $this->pendingQuantity($line);

            if ($pending <= 0.0) {
                continue;
            }

            $lines[] = new EntryLineData(
                id: null,
                itemId: (string) $line->item_id,
                measurementUnitId: (string) $line->measurement_unit_id,
                quantity: $pending,
                /** Nada se ha inspeccionado todavía: lo aceptado es todo lo que se espera. */
                rejectedQuantity: 0.0,
                receivedQuantity: $pending,
                unitPrice: 0.0,
                discountPercent: 0.0,
                discountAmount: 0.0,
                taxPercent: 0.0,
                taxAmount: 0.0,
                withholdingPercent: 0.0,
                withholdingAmount: 0.0,
                subtotal: 0.0,
                total: 0.0,
                sourceableType: PurchaseOrderLine::MORPH_ALIAS,
                sourceableId: $line->id,
            );
        }

        return $lines;
    }

    /**
     * Lo que le queda por llegar a la línea. Un artículo sin existencia no
     * llega nunca: la entrada no lo trae.
     */
    private function pendingQuantity(PurchaseOrderLine $line): float
    {
        $item = $line->item;

        if ($item instanceof Item && ! $item->movesStock()) {
            return 0.0;
        }

        return max(round((float) $line->quantity - (float) $line->received_quantity, 4), 0.0);
    }
}
