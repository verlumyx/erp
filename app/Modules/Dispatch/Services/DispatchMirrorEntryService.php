<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Commands\EntryLineData;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Entry\Services\EntryPricingService;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Transfer\Models\Transfer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * La entrada espejo del despacho: el documento que mete en la bodega de destino
 * lo que el despacho sacó de la de origen.
 *
 * Solo tiene sentido cuando detrás del despacho hay un traslado. Un despacho de
 * venta no genera entrada: la mercancía se fue con el cliente y dejó de ser de
 * la empresa.
 *
 * Nace al confirmar el despacho —en el mismo acto en que la mercancía sale— y
 * queda en borrador esperando en el destino a que alguien la confirme al verla
 * llegar. Ese confirmar es el que cierra el traslado.
 *
 * La entrada cuelga del **traslado**, no del despacho que la escribe: el
 * documento que originó el movimiento es el traslado, y es el que hay que
 * reconocer al mirar la entrada. El despacho queda trazado línea a línea, que
 * es donde de verdad hace falta —de ahí sale el costo con el que la mercancía
 * viajó—.
 */
class DispatchMirrorEntryService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $dispatches,
        private readonly EntryRepositoryInterface $entries,
        private readonly EntryPricingService $pricing,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * Crea la entrada que recibirá en destino lo que este despacho saca.
     *
     * Devuelve `null` cuando no hay nada que recibir: un despacho de venta, uno
     * sin líneas con existencia, o uno que ya tiene su entrada.
     */
    public function create(Dispatch $dispatch): ?Entry
    {
        $transfer = $dispatch->sourceable;

        if (! $dispatch->servesTransfer() || ! $transfer instanceof Transfer) {
            return null;
        }

        if ($this->liveEntry($dispatch) instanceof Entry) {
            return null;
        }

        $lines = $this->shippedLines($dispatch);

        if ($lines === []) {
            return null;
        }

        $id = (string) Str::uuid7();
        $entryDate = $dispatch->dispatch_date?->toDateString() ?? now()->toDateString();
        $currency = $this->companyCurrency($dispatch);

        $this->entries->create(
            new CreateEntryCommand(
                id: $id,
                companyId: (string) $dispatch->company_id,
                /** La mercancía llega a la bodega a la que va dirigido el despacho. */
                warehouseId: (string) $dispatch->recipient_id,
                entryDate: $entryDate,
                createdBy: (string) $dispatch->created_by,
                lines: $lines,
                /** Un traslado no tiene proveedor: la mercancía ya era de la empresa. */
                supplierId: null,
                /** El movimiento lo originó el traslado, no el despacho que lo ejecuta. */
                sourceableType: Transfer::MORPH_ALIAS,
                sourceableId: $transfer->id,
                entryType: Entry::TRANSFER_TYPE,
                currency: $currency,
                notes: "Generada al confirmar el despacho {$dispatch->code} del traslado {$transfer->code}.",
            ),
            $this->rates->forDocument((string) $dispatch->company_id, $currency, $entryDate),
            /**
             * El costo no se decide aquí: viaja con la mercancía desde el
             * origen, y lo trae la línea del despacho que cada línea recibe.
             */
            $this->pricing->apply(
                (string) $dispatch->company_id,
                Transfer::MORPH_ALIAS,
                $transfer->id,
                $lines,
            ),
        );

        return $this->entries->findOrFail($id, $dispatch->company_id);
    }

    /**
     * Anula la entrada en borrador del despacho, si la hay. Lo llama el
     * despacho al anularse: lo que no salió tampoco llega.
     */
    public function cancel(Dispatch $dispatch): void
    {
        $entry = $this->liveEntry($dispatch);

        if (! $entry instanceof Entry || $entry->status !== 'draft') {
            return;
        }

        $this->entries->updateStatus($entry, new UpdateStatusEntryCommand(status: 'cancelled'));
    }

    /**
     * Un despacho cuya mercancía ya entró en destino no se anula por las
     * buenas: primero hay que anular la entrada que la metió.
     *
     * @throws ValidationException
     */
    public function guardCancellable(Dispatch $dispatch): void
    {
        $entry = $this->liveEntry($dispatch);

        if (! $entry instanceof Entry || ! in_array($entry->status, Entry::POSTED_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "El despacho ya tiene la entrada {$entry->code} confirmada: anúlala primero.",
        ]);
    }

    /**
     * La entrada del despacho que todavía cuenta.
     *
     * Se busca por el traslado, que es de quien cuelga. Un traslado tiene un
     * solo despacho vivo a la vez —el suyo—, así que su entrada viva es la de
     * este despacho. Una anulada no estorba: el despacho puede volver a
     * generar la suya.
     */
    public function liveEntry(Dispatch $dispatch): ?Entry
    {
        if (blank($dispatch->sourceable_id) || ! $dispatch->servesTransfer()) {
            return null;
        }

        $result = $this->entries->search(new SearchEntryCommand(
            filters: ['sourceable_id' => $dispatch->sourceable_id],
            limit: 50,
            companyId: $dispatch->company_id,
        ));

        foreach ($result['data'] as $entry) {
            if ($entry->status !== 'cancelled') {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Lo que el despacho saca, como líneas de la entrada.
     *
     * El lote y la serie ya se eligieron al despachar y viajan con el
     * movimiento del kardex; la entrada no vuelve a pedirlos.
     *
     * @return array<int, EntryLineData>
     */
    private function shippedLines(Dispatch $dispatch): array
    {
        $lines = [];

        foreach ($this->dispatches->activeLines($dispatch) as $line) {
            $quantity = round((float) $line->quantity, 4);

            if ($quantity <= 0) {
                continue;
            }

            $lines[] = new EntryLineData(
                id: null,
                itemId: (string) $line->item_id,
                measurementUnitId: (string) $line->measurement_unit_id,
                quantity: $quantity,
                /** Nada se ha inspeccionado todavía: lo aceptado es todo lo que viaja. */
                rejectedQuantity: 0.0,
                receivedQuantity: $quantity,
                unitPrice: 0.0,
                discountPercent: 0.0,
                discountAmount: 0.0,
                taxPercent: 0.0,
                taxAmount: 0.0,
                withholdingPercent: 0.0,
                withholdingAmount: 0.0,
                subtotal: 0.0,
                total: 0.0,
                sourceableType: DispatchLine::MORPH_ALIAS,
                sourceableId: $line->id,
            );
        }

        return $lines;
    }

    /**
     * La moneda en la que la empresa valora sus existencias. Un traslado no
     * factura, así que la entrada se queda en la moneda del inventario.
     */
    private function companyCurrency(Dispatch $dispatch): string
    {
        return $this->configurations->execute((string) $dispatch->company_id)->base_currency;
    }
}
