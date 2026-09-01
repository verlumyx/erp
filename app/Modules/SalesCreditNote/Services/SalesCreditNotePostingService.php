<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Models\SalesCreditNoteLine;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que la nota deja de ser un papel y la mercancía vuelve a la
 * bodega.
 *
 * Confirmarla escribe una entrada en el kardex por cada línea; anularla emite
 * la contrapartida. Mientras está en borrador sus líneas ya están escritas,
 * pero ninguna existencia se ha movido.
 *
 * La entrada se valora **al costo original de la venta** —el `unit_cost` que la
 * línea congeló de la factura— y no al promedio vigente: devolver mercancía no
 * puede inventar ni destruir margen.
 *
 * Una nota que no afecta inventario no pasa por aquí: solo baja la cuenta por
 * cobrar.
 */
class SalesCreditNotePostingService
{
    public function __construct(
        private readonly SalesCreditNoteRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
    ) {}

    /** Reingresa la mercancía. */
    public function post(SalesCreditNote $note): void
    {
        if ($note->affects_inventory !== 'yes') {
            return;
        }

        DB::transaction(function () use ($note): void {
            foreach ($this->repository->activeLines($note) as $line) {
                $this->registerEntry($note, $line);
            }
        });
    }

    /**
     * Deshace la entrada: cada movimiento del kardex recibe su contrapartida.
     * Ninguna fila se borra.
     */
    public function reverse(SalesCreditNote $note): void
    {
        DB::transaction(function () use ($note): void {
            foreach ($this->postedMovements($note) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación de la nota de crédito {$note->code}.",
                    createdBy: $note->created_by,
                ));
            }
        });
    }

    /**
     * Un asiento de entrada por línea, en unidad base y al costo de la venta.
     *
     * Un artículo sin existencia —un servicio colado en la nota— no llega al
     * kardex: la línea vale para el documento y para el crédito, pero no hay
     * saldo que mover.
     */
    private function registerEntry(SalesCreditNote $note, SalesCreditNoteLine $line): void
    {
        if (blank($line->warehouse_id)) {
            throw ValidationException::withMessages([
                'status' => "La línea {$line->line_number} no dice a qué bodega reingresa la mercancía.",
            ]);
        }

        try {
            $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $note->company_id,
                itemId: $line->item_id,
                warehouseId: $line->warehouse_id,
                locationId: $this->locationFor($note, $line),
                type: 'in',
                originType: SalesCreditNote::MOVEMENT_ORIGIN_TYPE,
                originId: $note->id,
                quantity: round((float) $line->base_quantity, 4),
                unitCost: round((float) $line->unit_cost, 6),
                movementDate: $note->note_date?->toDateString(),
                originLineId: $line->id,
                lotId: $line->lot_id,
                notes: $line->notes,
                createdBy: $note->created_by,
            ));
        } catch (NonInventoriedItemException) {
            return;
        }
    }

    /**
     * Movimientos vivos que escribió esta nota. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas otra
     * vez volvería a meter la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(SalesCreditNote $note): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => SalesCreditNote::MOVEMENT_ORIGIN_TYPE,
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
     * A dónde entra físicamente la mercancía: la ubicación por defecto de la
     * bodega de la línea. Sin ninguna, la nota no se confirma: el kardex no
     * mueve saldo sin sitio.
     */
    private function locationFor(SalesCreditNote $note, SalesCreditNoteLine $line): string
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
