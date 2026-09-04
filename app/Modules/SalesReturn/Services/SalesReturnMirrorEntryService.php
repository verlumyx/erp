<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Commands\EntryLineData;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * La entrada espejo de la devolución de venta: el documento que sí reingresa la
 * mercancía que el cliente trajo de vuelta.
 *
 * La devolución es el acuerdo con el cliente —qué vuelve y cuánto se le
 * acredita—, no el reingreso físico. Confirmarla apunta el cupo en la factura y
 * deja escrito lo que hay que recibir: una entrada `ENT` en borrador, colgada
 * de la devolución y del tipo `return`. Bodega abre ese borrador, completa lo
 * físico —ubicación, lotes, series, lo que de verdad llegó— y lo confirma. Es
 * ese confirmar, y no el de la devolución, el que toca el kardex.
 *
 * La mercancía reingresa al **costo congelado en la venta**, no al promedio
 * vigente: si se valorara al promedio, devolver inventaría o destruiría margen
 * sin que nadie comprara ni vendiera nada. Ese costo lo resolvió
 * `SalesReturnCostService` y vive en `unit_cost` de la línea devuelta; aquí se
 * convierte a la unidad de la línea para que la entrada lo derive de vuelta.
 *
 * La entrada recibe en la bodega de la **cabecera** de la devolución: una
 * entrada carga en una sola bodega, y esa es la que la devolución declara.
 *
 * Lo que vuelve para destruirse (`scrap`) no viaja a la entrada: no reingresa a
 * ninguna bodega y su pérdida se registra por Ajuste. Un artículo sin
 * existencia —un servicio, una instalación facturada— tampoco.
 */
class SalesReturnMirrorEntryService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $returns,
        private readonly EntryRepositoryInterface $entries,
        private readonly ItemRepositoryInterface $items,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Crea la entrada que reingresará la mercancía devuelta.
     *
     * Devuelve `null` cuando no hay nada que recibir: una devolución que se
     * destruye entera, una solo de servicios, o una que ya tiene su entrada.
     */
    public function create(SalesReturn $return): ?Entry
    {
        if ($this->liveEntry($return) instanceof Entry) {
            return null;
        }

        $lines = $this->stockedLines($return);

        if ($lines === []) {
            return null;
        }

        $id = (string) Str::uuid7();
        $entryDate = $return->return_date?->toDateString() ?? now()->toDateString();

        $this->entries->create(
            new CreateEntryCommand(
                id: $id,
                companyId: (string) $return->company_id,
                warehouseId: (string) $return->warehouse_id,
                entryDate: $entryDate,
                createdBy: (string) $return->created_by,
                lines: $lines,
                /** Lo que vuelve de un cliente no se le compra a nadie. */
                supplierId: null,
                sourceableType: SalesReturn::MORPH_ALIAS,
                sourceableId: $return->id,
                entryType: Entry::RETURN_TYPE,
                currency: (string) $return->currency,
                notes: "Generada al confirmar la devolución {$return->code}.",
            ),
            /**
             * La entrada se valora con la tasa de **su** fecha, no con la de la
             * devolución: son dos documentos y cada uno congela la suya
             * (`docs/logistica.md` §3).
             */
            $this->rates->forDocument((string) $return->company_id, (string) $return->currency, $entryDate),
            $lines,
        );

        return $this->entries->findOrFail($id, $return->company_id);
    }

    /**
     * Anula la entrada en borrador de la devolución, si la hay. La llama la
     * devolución al anularse: lo que ya no vuelve tampoco se recibe.
     */
    public function cancel(SalesReturn $return): void
    {
        $entry = $this->liveEntry($return);

        if (! $entry instanceof Entry || $entry->status !== 'draft') {
            return;
        }

        $this->entries->updateStatus($entry, new UpdateStatusEntryCommand(status: 'cancelled'));
    }

    /**
     * Una devolución con mercancía ya reingresada no se anula por las buenas:
     * primero hay que anular la entrada que la metió, que es la que sabe
     * deshacer su propio asiento en el kardex.
     *
     * @throws ValidationException
     */
    public function guardCancellable(SalesReturn $return): void
    {
        $entry = $this->liveEntry($return);

        if (! $entry instanceof Entry || ! in_array($entry->status, Entry::POSTED_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "La devolución ya tiene la entrada {$entry->code} confirmada: anúlala primero.",
        ]);
    }

    /**
     * La entrada de la devolución que todavía cuenta. Una anulada no estorba:
     * la devolución puede volver a generar la suya.
     */
    public function liveEntry(SalesReturn $return): ?Entry
    {
        $result = $this->entries->search(new SearchEntryCommand(
            filters: ['sourceable_id' => $return->id],
            limit: 50,
            companyId: $return->company_id,
        ));

        foreach ($result['data'] as $entry) {
            if ($entry->status !== 'cancelled') {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Lo que la devolución reingresa, como líneas de la entrada.
     *
     * Van sin lote y sin serie a propósito: la devolución ya no los captura, y
     * el número de la caja lo lee quien recibe. Nada se ha inspeccionado
     * todavía, así que lo aceptado es todo lo que se espera.
     *
     * @return array<int, EntryLineData>
     */
    private function stockedLines(SalesReturn $return): array
    {
        /** Lo que se destruye no reingresa: la condición de la cabecera manda. */
        if ($return->condition === SalesReturn::SCRAP_CONDITION) {
            return [];
        }

        $lines = [];

        foreach ($this->returns->activeLines($return) as $line) {
            $quantity = $this->stockedQuantity($line);

            if ($quantity <= 0.0) {
                continue;
            }

            /**
             * `unit_cost` está en unidad base y `unit_price` es por unidad de la
             * línea: multiplicar por el factor deja que la entrada derive de
             * vuelta el mismo costo por unidad base.
             */
            $unitPrice = round(
                (float) $line->unit_cost * $this->factorFor($return->company_id, $line),
                6,
            );

            $amount = round($quantity * $unitPrice, 2);

            $lines[] = new EntryLineData(
                id: null,
                itemId: (string) $line->item_id,
                measurementUnitId: (string) $line->measurement_unit_id,
                quantity: $quantity,
                rejectedQuantity: 0.0,
                receivedQuantity: $quantity,
                unitPrice: $unitPrice,
                discountPercent: 0.0,
                discountAmount: 0.0,
                taxPercent: 0.0,
                taxAmount: 0.0,
                withholdingPercent: 0.0,
                withholdingAmount: 0.0,
                subtotal: $amount,
                total: $amount,
                sourceableType: SalesReturnLine::MORPH_ALIAS,
                sourceableId: $line->id,
            );
        }

        return $lines;
    }

    /**
     * Lo que la línea reingresa. Un artículo sin existencia no vuelve nunca a
     * una bodega: la entrada no lo trae.
     */
    private function stockedQuantity(SalesReturnLine $line): float
    {
        $item = $line->item;

        if ($item instanceof Item && ! $item->movesStock()) {
            return 0.0;
        }

        return max(round((float) $line->quantity, 4), 0.0);
    }

    /**
     * Factor con el que la unidad de la línea se convierte a la unidad base.
     * Se resuelve por el repositorio de artículos: este módulo nunca consulta
     * las tablas del inventario directamente.
     */
    private function factorFor(?string $companyId, SalesReturnLine $line): float
    {
        $item = $this->items->findById((string) $line->item_id, $companyId);

        if (! $item instanceof Item) {
            return 1.0;
        }

        foreach ($item->units as $unit) {
            /** @var ItemUnit $unit */
            if ($unit->measurement_unit_id === $line->measurement_unit_id) {
                return (float) $unit->conversion_factor;
            }
        }

        return 1.0;
    }
}
