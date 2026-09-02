<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

/**
 * Lo que impide retirar un artículo del catálogo.
 *
 * Desactivarlo con existencia distinta de cero dejaría mercancía real fuera del
 * alcance de los documentos, y hacerlo con pedidos abiertos dejaría promesas
 * que ya no se pueden cumplir (`docs/inventario.md` §1).
 *
 * «Documento abierto» son los pedidos —de venta y de compra— que todavía
 * esperan mercancía: los que están en `draft`, `confirmed` o `partial`. Un
 * documento cumplido o anulado ya no reclama nada del artículo.
 */
class ItemUsageService
{
    public function __construct(
        private readonly ItemStockRepositoryInterface $stocks,
    ) {}

    /** Existencia viva del artículo en toda la empresa. */
    public function stockedQuantity(?string $companyId, string $itemId): float
    {
        return round($this->stocks->companyBalance($companyId, $itemId)['quantity'], 4);
    }

    /** Si algún pedido vivo lo está esperando. */
    public function hasOpenDocuments(?string $companyId, string $itemId): bool
    {
        $inSalesOrders = SalesOrderLine::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('item_id', $itemId)
            ->where('status', 'active')
            ->whereHas('salesOrder', fn ($q) => $q->whereIn('status', SalesOrder::OPEN_STATUSES))
            ->exists();

        if ($inSalesOrders) {
            return true;
        }

        return PurchaseOrderLine::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('item_id', $itemId)
            ->where('status', 'active')
            ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', PurchaseOrder::OPEN_STATUSES))
            ->exists();
    }
}
