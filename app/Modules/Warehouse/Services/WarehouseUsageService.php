<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\SalesOrder\Models\SalesOrder;

/**
 * Lo que impide cerrar una bodega.
 *
 * Desactivarla con mercancía dentro la dejaría fuera del alcance de los
 * documentos —nadie podría sacarla ni contarla—, y hacerlo con pedidos
 * pendientes dejaría promesas de entrada o de salida sin sitio a donde ir
 * (`docs/inventario.md` §2).
 *
 * «Documento pendiente» son los pedidos —de venta y de compra— que todavía la
 * señalan y siguen vivos: los que están en `draft`, `confirmed` o `partial`.
 */
class WarehouseUsageService
{
    public function __construct(
        private readonly ItemStockRepositoryInterface $stocks,
    ) {}

    /** Existencia total que la bodega guarda hoy. */
    public function stockedQuantity(?string $companyId, string $warehouseId): float
    {
        return $this->stocks->warehouseQuantity($companyId, $warehouseId);
    }

    /** Si algún pedido vivo la está usando. */
    public function hasOpenDocuments(?string $companyId, string $warehouseId): bool
    {
        $inSalesOrders = SalesOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', SalesOrder::OPEN_STATUSES)
            ->exists();

        if ($inSalesOrders) {
            return true;
        }

        return PurchaseOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', PurchaseOrder::OPEN_STATUSES)
            ->exists();
    }
}
