<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Las dos condiciones de la factura que no salen del formulario: el
 * vencimiento, que se deriva del crédito del proveedor, y la validez del
 * documento origen.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: la factura se edita en borrador y cada guardado vuelve a comprobar lo
 * mismo.
 */
class PurchaseInvoiceTermsService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
    ) {}

    /**
     * Vencimiento de la factura: el que escribió el usuario o, si lo dejó
     * vacío, la fecha de emisión más los días de crédito del proveedor.
     */
    public function dueDate(?string $dueDate, string $invoiceDate, string $supplierId, ?string $companyId): string
    {
        if (filled($dueDate)) {
            return $dueDate;
        }

        $days = (int) ($this->suppliers->findById($supplierId, $companyId)?->payment_term_days ?? 0);

        return Carbon::parse($invoiceDate)->addDays($days)->toDateString();
    }

    /**
     * El documento origen no es una foreign key —es un par polimórfico—, así
     * que la base de datos no puede garantizar su integridad: se comprueba
     * aquí, antes de guardar. Debe existir y pertenecer a la misma empresa y al
     * mismo proveedor que la factura.
     *
     * @throws ValidationException
     */
    public function guardSourceDocument(
        ?string $sourceableType,
        ?string $sourceableId,
        string $supplierId,
        ?string $companyId,
    ): void {
        if (blank($sourceableType) || blank($sourceableId)) {
            return;
        }

        if (! in_array($sourceableType, PurchaseInvoice::SOURCE_TYPES, true)) {
            throw ValidationException::withMessages([
                'sourceable_type' => 'El tipo de documento origen no está admitido.',
            ]);
        }

        $order = $this->purchaseOrders->findById($sourceableId, $companyId);

        if (! $order instanceof PurchaseOrder) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'La orden de compra indicada no existe en esta empresa.',
            ]);
        }

        if ($order->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'La orden de compra pertenece a otro proveedor.',
            ]);
        }
    }
}
