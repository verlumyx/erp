<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\Item\Models\Item;
use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Services\ItemStockApplyMovementService;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * La mercancía que ya se pidió y todavía no ha llegado.
 *
 * Confirmar una orden de compra no mete nada en la bodega, pero sí la anuncia:
 * sube el `incoming_quantity` de la existencia, que es lo que deja ver a quien
 * planifica que ese artículo tiene reposición en camino
 * (`docs/inventario.md` §3). Anular la orden lo borra, y recibir la mercancía
 * lo consume: lo que ya entró dejó de estar en tránsito.
 *
 * Lo anunciado se anota en la ubicación por defecto de la bodega. No es una
 * existencia física —todavía no hay nada que colocar—, así que basta con que el
 * saldo de la bodega lo refleje.
 *
 * Un artículo sin existencia —un servicio, un flete contratado en la misma
 * orden— no anuncia nada: no hay saldo que mover.
 */
class PurchaseOrderIncomingService
{

    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly ItemStockApplyMovementService $stocks,
        private readonly WarehouseLocationRepositoryInterface $locations,
    ) {}

    /** Anuncia la mercancía de la orden como mercancía en camino. */
    public function announce(PurchaseOrder $order): void
    {
        $this->move($order, 1);
    }

    /** Borra lo anunciado: la orden se anuló y esa mercancía ya no viene. */
    public function withdraw(PurchaseOrder $order): void
    {
        $this->move($order, -1);
    }

    /**
     * Consume lo anunciado de un artículo cuando la mercancía entra de verdad.
     * Lo llama la entrada al confirmarse, y con el signo contrario cuando esa
     * entrada se anula y la mercancía vuelve a estar por llegar.
     */
    public function receive(
        ?string $companyId,
        string $warehouseId,
        string $itemId,
        float $baseQuantityReceived,
    ): void {
        $this->moveStockIncoming($companyId, $warehouseId, $itemId, -$baseQuantityReceived);
    }

    /** `$sign` vale 1 al confirmar la orden y -1 al anularla. */
    private function move(PurchaseOrder $order, int $sign): void
    {
        DB::transaction(function () use ($order, $sign): void {
            foreach ($this->repository->activeLines($order) as $line) {
                $pending = $this->stockedQuantity($line);

                if ($pending <= 0.0) {
                    continue;
                }

                $this->moveStockIncoming(
                    $order->company_id,
                    $order->warehouse_id,
                    $line->item_id,
                    $sign * $pending,
                );
            }
        });
    }

    /** Mueve lo anunciado de una existencia, con signo. */
    private function moveStockIncoming(
        ?string $companyId,
        string $warehouseId,
        string $itemId,
        float $baseQuantityDelta,
    ): void {
        if (blank($companyId) || round($baseQuantityDelta, 4) === 0.0) {
            return;
        }

        $location = $this->defaultLocation((string) $companyId, $warehouseId);

        if ($location === null) {
            return;
        }

        $this->stocks->execute(new ApplyItemStockMovementCommand(
            companyId: (string) $companyId,
            itemId: $itemId,
            warehouseId: $warehouseId,
            locationId: $location->id,
            incomingDelta: round($baseQuantityDelta, 4),
        ));
    }

    /**
     * Lo que a la línea le queda por llegar, en unidad base. Una línea ya
     * recibida no vuelve a anunciarse, y un artículo sin existencia nunca se
     * anuncia.
     */
    private function stockedQuantity(PurchaseOrderLine $line): float
    {
        $item = $line->item;

        if ($item instanceof Item && ! $item->movesStock()) {
            return 0.0;
        }

        $ordered = round((float) $line->quantity, 4);
        $pending = round((float) $line->pending_quantity, 4);

        if ($ordered <= 0) {
            return 0.0;
        }

        return round($pending * (float) $line->base_quantity / $ordered, 4);
    }

    /** La ubicación por defecto de la bodega, si tiene una. */
    private function defaultLocation(string $companyId, string $warehouseId): ?WarehouseLocation
    {
        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $warehouseId,
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $companyId,
        ));

        $location = $result['data'][0] ?? null;

        return $location instanceof WarehouseLocation ? $location : null;
    }
}
