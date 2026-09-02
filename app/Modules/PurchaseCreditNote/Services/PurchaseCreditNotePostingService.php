<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNoteLine;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que la nota deja de ser un papel y baja lo que se le debe al
 * proveedor.
 *
 * Confirmarla descarga su cuenta por pagar y —solo si la mercancía sale de
 * verdad— escribe una salida en el kardex por cada línea; anularla emite la
 * contrapartida y devuelve el saldo. Mientras está en borrador sus líneas ya
 * están escritas, pero ningún saldo se ha movido.
 *
 * La salida se valora **al costo de la compra original** —el `landed_cost` de la
 * línea facturada— y no al promedio vigente: devolverle mercancía al proveedor
 * no puede inventar ni destruir costo.
 *
 * Una nota que no afecta inventario no mueve existencia: solo baja la cuenta
 * por pagar.
 */
class PurchaseCreditNotePostingService
{
    public function __construct(
        private readonly PurchaseCreditNoteRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly SupplierApplyBalanceService $applyToSupplier,
    ) {}

    /** Acredita la nota: baja la deuda y, si toca, saca la mercancía. */
    public function post(PurchaseCreditNote $note): void
    {
        DB::transaction(function () use ($note): void {
            if ($note->affects_inventory === 'yes') {
                foreach ($this->repository->activeLines($note) as $line) {
                    $this->registerExit($note, $line);
                }
            }

            $this->moveSupplierBalance($note, -round((float) $note->total, 2));
        });
    }

    /**
     * Deshace el crédito: la deuda con el proveedor vuelve a subir y cada
     * movimiento del kardex recibe su contrapartida. Ninguna fila se borra.
     */
    public function reverse(PurchaseCreditNote $note): void
    {
        DB::transaction(function () use ($note): void {
            foreach ($this->postedMovements($note) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación de la nota de crédito {$note->code}.",
                    createdBy: $note->created_by,
                ));
            }

            $this->moveSupplierBalance($note, round((float) $note->total, 2));
        });
    }

    /**
     * Un asiento de salida por línea, en unidad base y al costo de la compra.
     *
     * Un artículo sin existencia —un servicio, un descuento acreditado como
     * línea— no llega al kardex: la línea vale para el documento y para el
     * crédito, pero no hay saldo que mover.
     */
    private function registerExit(PurchaseCreditNote $note, PurchaseCreditNoteLine $line): void
    {
        if (blank($line->warehouse_id)) {
            throw ValidationException::withMessages([
                'status' => "La línea {$line->line_number} no dice de qué bodega sale la mercancía.",
            ]);
        }

        try {
            $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $note->company_id,
                itemId: $line->item_id,
                warehouseId: $line->warehouse_id,
                locationId: $this->locationFor($note, $line),
                type: 'out',
                originType: PurchaseCreditNote::MOVEMENT_ORIGIN_TYPE,
                originId: $note->id,
                quantity: round((float) $line->base_quantity, 4),
                unitCost: $this->originalCost($line),
                movementDate: $note->note_date?->toDateString(),
                originLineId: $line->id,
                lotId: $line->lot_id,
                notes: $line->notes,
                createdBy: $note->created_by,
            ));
        } catch (NonInventoriedItemException) {
            return;
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'status' => "La bodega no tiene existencia suficiente para acreditar la línea {$line->line_number}.",
            ]);
        }
    }

    /**
     * Movimientos vivos que escribió esta nota. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas otra
     * vez volvería a sacar la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(PurchaseCreditNote $note): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => PurchaseCreditNote::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => $note->id,
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $note->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (InventoryMovement $movement): bool => $movement->reversal_of_id === null,
        ));
    }

    /**
     * Costo unitario con el que la mercancía entró: el costo final de la línea
     * facturada, con flete y gastos ya prorrateados, y si no lo tiene su precio
     * de compra. Sin línea de factura manda el precio de la propia nota.
     */
    private function originalCost(PurchaseCreditNoteLine $line): float
    {
        $invoiceLine = $line->purchaseInvoiceLine;

        if ($invoiceLine instanceof PurchaseInvoiceLine) {
            $landed = round((float) $invoiceLine->landed_cost, 6);

            return $landed > 0 ? $landed : round((float) $invoiceLine->unit_price, 6);
        }

        return round((float) $line->unit_price, 6);
    }

    /** Descarga (o devuelve) la cuenta por pagar del proveedor. */
    private function moveSupplierBalance(PurchaseCreditNote $note, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->applyToSupplier->execute(new ApplySupplierBalanceCommand(
            companyId: $note->company_id,
            supplierId: $note->supplier_id,
            currentBalanceDelta: $delta,
        ));
    }

    /**
     * De dónde sale físicamente la mercancía: la ubicación por defecto de la
     * bodega de la línea. Sin ninguna, la nota no se confirma: el kardex no
     * mueve saldo sin sitio.
     */
    private function locationFor(PurchaseCreditNote $note, PurchaseCreditNoteLine $line): string
    {
        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $line->warehouse_id,
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $note->company_id,
        ));

        $location = $result['data'][0] ?? null;

        if (! $location instanceof WarehouseLocation) {
            throw ValidationException::withMessages([
                'status' => "La bodega de la línea {$line->line_number} no tiene ubicación por defecto.",
            ]);
        }

        return $location->id;
    }
}
