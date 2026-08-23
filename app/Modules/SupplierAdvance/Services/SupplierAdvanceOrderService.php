<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * La única condición del anticipo que no sale del formulario: que la orden que
 * lo motiva sea de verdad de este proveedor y de esta empresa.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la
 * necesitan igual: el anticipo se edita en borrador y cada guardado vuelve a
 * comprobar lo mismo.
 */
class SupplierAdvanceOrderService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
    ) {}

    /**
     * La orden es opcional —hay anticipos que no nacen de ninguna—, pero si
     * viene tiene que existir y pertenecer al mismo proveedor.
     *
     * @throws ValidationException
     */
    public function guardPurchaseOrder(?string $purchaseOrderId, string $supplierId, ?string $companyId): void
    {
        if (blank($purchaseOrderId)) {
            return;
        }

        $order = $this->purchaseOrders->findById($purchaseOrderId, $companyId);

        if (! $order instanceof PurchaseOrder) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'La orden de compra indicada no existe en esta empresa.',
            ]);
        }

        if ($order->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'La orden de compra pertenece a otro proveedor.',
            ]);
        }
    }
}
