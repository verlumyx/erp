<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\SupplierPayment\Commands\SupplierPaymentApplicationData;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Illuminate\Validation\ValidationException;

/**
 * Las comprobaciones del pago que la base de datos no puede hacer por su
 * cuenta: el origen no es una foreign key —apunta a dos tablas según el tipo—
 * y la factura de cada aplicación tiene que ser del mismo proveedor, estar viva
 * y tener saldo.
 *
 * Vive aparte de los servicios de acción porque Crear, Actualizar y Confirmar
 * la necesitan igual: entre que se captura el reparto y se confirma el pago,
 * otro documento pudo haberse llevado el saldo.
 *
 * @throws ValidationException
 */
class SupplierPaymentOriginService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $purchaseInvoices,
    ) {}

    /**
     * El origen del pago. `advance` no entra por aquí: ese pago nace al
     * aprobar un anticipo, no desde esta pantalla.
     */
    public function guardOrigin(string $originType, ?string $originId, string $supplierId, ?string $companyId): void
    {
        if (! in_array($originType, SupplierPayment::CREATABLE_ORIGIN_TYPES, true)) {
            throw ValidationException::withMessages([
                'origin_type' => 'Un pago solo se inicia desde un proveedor o desde una factura.',
            ]);
        }

        if ($originType === 'supplier') {
            if (filled($originId)) {
                throw ValidationException::withMessages([
                    'origin_id' => 'Un pago iniciado desde el proveedor no apunta a ningún documento.',
                ]);
            }

            return;
        }

        if (blank($originId)) {
            throw ValidationException::withMessages([
                'origin_id' => 'Falta la factura desde la que se inicia el pago.',
            ]);
        }

        $this->payableInvoice($originId, $supplierId, $companyId, 'origin_id');
    }

    /**
     * El reparto entre facturas. Cada una tiene que seguir teniendo saldo por
     * lo que se le quiere abonar: se comprueba al guardar y otra vez al
     * confirmar.
     *
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     */
    public function guardApplications(array $applications, string $supplierId, ?string $companyId): void
    {
        foreach ($applications as $index => $row) {
            if ($row->status !== 'active') {
                continue;
            }

            $invoice = $this->payableInvoice(
                $row->purchaseInvoiceId,
                $supplierId,
                $companyId,
                "applications.{$index}.purchase_invoice_id",
            );

            if ($row->appliedAmount > round((float) $invoice->balance, 2)) {
                throw ValidationException::withMessages([
                    "applications.{$index}.applied_amount" => "La factura {$invoice->code} solo debe {$invoice->balance}.",
                ]);
            }
        }
    }

    /**
     * La factura existe en esta empresa, es de este proveedor, ya generó su
     * deuda y todavía debe algo.
     */
    private function payableInvoice(
        string $invoiceId,
        string $supplierId,
        ?string $companyId,
        string $field,
    ): PurchaseInvoice {
        $invoice = $this->purchaseInvoices->findById($invoiceId, $companyId);

        if (! $invoice instanceof PurchaseInvoice) {
            throw ValidationException::withMessages([
                $field => 'La factura indicada no existe en esta empresa.',
            ]);
        }

        if ($invoice->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                $field => 'La factura pertenece a otro proveedor: un pago no cruza proveedores.',
            ]);
        }

        if (! in_array($invoice->status, PurchaseInvoice::PAYABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                $field => "La factura {$invoice->code} no admite pagos en su estado actual.",
            ]);
        }

        if (round((float) $invoice->balance, 2) <= 0) {
            throw ValidationException::withMessages([
                $field => "La factura {$invoice->code} ya está saldada.",
            ]);
        }

        return $invoice;
    }
}
