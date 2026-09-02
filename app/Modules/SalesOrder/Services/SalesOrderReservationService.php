<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\Item\Models\Item;
use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\ItemStock\Services\ItemStockApplyMovementService;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El pedido comprometiendo mercancía que todavía no ha salido.
 *
 * Un pedido de venta **no descarga inventario**: lo reserva. Confirmarlo
 * comprueba que la bodega tenga disponible lo que se prometió y sube el
 * `reserved_quantity` de esa existencia, de modo que el siguiente pedido ya no
 * pueda contar con ella. Anularlo lo suelta, y despacharlo también: lo que
 * salió de la bodega dejó de estar comprometido porque ya no está
 * (`docs/ventas.md` §2.2).
 *
 * Lo reservado se guarda en la ubicación por defecto de la bodega. La reserva
 * no es física —nadie aparta una caja de un estante—, así que basta con que el
 * saldo de la bodega la refleje.
 *
 * Un artículo sin existencia —un servicio, una cuota de instalación— no reserva
 * nada: no hay saldo que comprometer.
 */
class SalesOrderReservationService
{
    /**
     * Tipos de artículo que no llevan existencia y por tanto no reservan.
     *
     * @var array<int, string>
     */
    private const NON_STOCKED_TYPES = ['service', 'non_inventoried'];

    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
        private readonly ItemStockApplyMovementService $stocks,
        private readonly ItemStockRepositoryInterface $balances,
        private readonly WarehouseLocationRepositoryInterface $locations,
    ) {}

    /**
     * Compromete la mercancía del pedido.
     *
     * @throws ValidationException si la bodega no tiene disponible lo prometido.
     */
    public function reserve(SalesOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            foreach ($this->repository->activeLines($order) as $line) {
                $quantity = $this->stockedQuantity($line);

                if ($quantity <= 0.0) {
                    continue;
                }

                $this->guardAvailable($order, $line, $quantity);
                $this->moveStockReservation($order->company_id, $order->warehouse_id, $line->item_id, $quantity);

                $this->repository->writeLineReservation($line, round((float) $line->quantity, 4));
            }
        });
    }

    /**
     * Suelta lo que el pedido tenía comprometido. Lo llama la anulación, y
     * también es lo que hace falta cuando un pedido confirmado deja de estarlo.
     */
    public function release(SalesOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            foreach ($this->repository->activeLines($order) as $line) {
                $reserved = round((float) $line->reserved_quantity, 4);

                if ($reserved <= 0.0) {
                    continue;
                }

                $this->moveStockReservation(
                    $order->company_id,
                    $order->warehouse_id,
                    $line->item_id,
                    -$this->toBaseUnits($line, $reserved),
                );

                $this->repository->writeLineReservation($line, 0);
            }
        });
    }

    /**
     * Mueve lo comprometido de una existencia, con signo. Lo usa el pedido al
     * confirmarse y al anularse, y el despacho cuando la mercancía sale de
     * verdad y la reserva deja de tener sentido.
     */
    public function moveStockReservation(
        ?string $companyId,
        string $warehouseId,
        string $itemId,
        float $baseQuantityDelta,
    ): void {
        if (blank($companyId) || $baseQuantityDelta === 0.0) {
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
            reservedDelta: round($baseQuantityDelta, 4),
        ));
    }

    /**
     * Lo que la línea compromete, en unidad base. Cero cuando el artículo no
     * lleva existencia.
     */
    private function stockedQuantity(SalesOrderLine $line): float
    {
        $item = $line->item;

        if ($item instanceof Item && in_array($item->type, self::NON_STOCKED_TYPES, true)) {
            return 0.0;
        }

        return round((float) $line->base_quantity, 4);
    }

    /** La misma proporción que la línea usó para convertir a unidad base. */
    private function toBaseUnits(SalesOrderLine $line, float $quantity): float
    {
        $ordered = round((float) $line->quantity, 4);

        if ($ordered <= 0) {
            return 0.0;
        }

        return round($quantity * (float) $line->base_quantity / $ordered, 4);
    }

    /**
     * Lo prometido tiene que caber en lo disponible: la existencia menos lo que
     * otros pedidos ya reservaron.
     */
    private function guardAvailable(SalesOrder $order, SalesOrderLine $line, float $quantity): void
    {
        $available = $this->balances->warehouseAvailable(
            $order->company_id,
            $line->item_id,
            $order->warehouse_id,
        );

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'status' => "La bodega solo tiene {$available} disponible para la línea {$line->line_number}.",
            ]);
        }
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
