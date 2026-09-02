<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\PurchaseInvoice\Commands\ApplyPurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceApplyPaymentService;
use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use App\Modules\SupplierPayment\Commands\ApplySupplierCreditCommand;
use App\Modules\SupplierPayment\Commands\WriteSupplierPaymentApplicationCommand;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El camino por el que un crédito que no es dinero —un anticipo `ANP` o una
 * nota de crédito `NCP`— abona una factura de compra.
 *
 * El pago tiene el suyo (`SupplierPaymentPostingService`): captura su reparto
 * en borrador y lo confirma después. Estos otros no: el crédito que los
 * respalda ya existe y ya movió el saldo del proveedor cuando el documento se
 * confirmó, así que la aplicación nace abonada y lo único que hace aquí es
 * repartirla entre facturas.
 *
 * Por eso **no** mueve `current_balance` del proveedor al aplicar: la nota ya lo
 * bajó al confirmarse y el anticipo entró como crédito a favor, no como deuda.
 * Lo que sí mueve es el `advance_balance` cuando el crédito que se gasta es un
 * anticipo: ese saldo a favor se consume.
 *
 * La transacción es anidable: llamado desde el documento que aplica se suma a
 * la suya como savepoint.
 */
class SupplierPaymentApplyCreditService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
        private readonly PurchaseInvoiceRepositoryInterface $purchaseInvoices,
        private readonly PurchaseInvoiceApplyPaymentService $applyToInvoice,
        private readonly SupplierApplyBalanceService $applyToSupplier,
    ) {}

    /** Abona la factura con el crédito indicado y deja escrita la fila. */
    public function apply(ApplySupplierCreditCommand $command): SupplierPaymentApplication
    {
        return DB::transaction(function () use ($command): SupplierPaymentApplication {
            $amount = round($command->appliedAmount, 2);

            $this->applyToInvoice->execute(new ApplyPurchaseInvoicePaymentCommand(
                companyId: $command->companyId,
                purchaseInvoiceId: $command->purchaseInvoiceId,
                appliedDelta: $amount,
            ));

            return $this->repository->writeApplication(new WriteSupplierPaymentApplicationCommand(
                companyId: $command->companyId,
                purchaseInvoiceId: $command->purchaseInvoiceId,
                sourceType: $command->sourceType,
                sourceId: $command->sourceId,
                appliedAmount: $amount,
                exchangeRate: $command->exchangeRate,
                exchangeDifference: $this->exchangeDifference($command, $amount),
                createdBy: $command->createdBy,
            ));
        });
    }

    /**
     * Deshace todo lo que un origen abonó: cada factura recupera su saldo y las
     * filas quedan en `reversed`. Ninguna se borra.
     *
     * Devuelve lo que se revirtió en total, que es lo que el origen tiene que
     * devolverse a sí mismo como disponible.
     */
    public function revert(?string $companyId, string $sourceType, string $sourceId): float
    {
        return DB::transaction(function () use ($companyId, $sourceType, $sourceId): float {
            $reverted = 0.0;

            foreach ($this->repository->applicationsOf($sourceType, $sourceId) as $application) {
                $amount = round((float) $application->applied_amount, 2);

                $this->applyToInvoice->execute(new ApplyPurchaseInvoicePaymentCommand(
                    companyId: $companyId,
                    purchaseInvoiceId: $application->purchase_invoice_id,
                    appliedDelta: -$amount,
                ));

                $this->repository->reverseApplication($application);

                $reverted += $amount;
            }

            return round($reverted, 2);
        });
    }

    /** Consume —o devuelve— el crédito a favor del proveedor. */
    public function moveAdvanceBalance(?string $companyId, string $supplierId, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->applyToSupplier->execute(new ApplySupplierBalanceCommand(
            companyId: $companyId,
            supplierId: $supplierId,
            advanceBalanceDelta: $delta,
        ));
    }

    /**
     * Lo que sobra o falta en bolívares entre la tasa con la que la factura
     * congeló su deuda y la que el crédito congeló al cancelarla.
     */
    private function exchangeDifference(ApplySupplierCreditCommand $command, float $amount): float
    {
        $invoice = $this->purchaseInvoices->findById($command->purchaseInvoiceId, $command->companyId);

        if (! $invoice instanceof PurchaseInvoice) {
            return 0.0;
        }

        return round($amount * ($command->exchangeRate - (float) $invoice->exchange_rate), 2);
    }
}
