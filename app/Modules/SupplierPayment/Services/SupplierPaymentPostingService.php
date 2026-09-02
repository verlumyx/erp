<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\PurchaseInvoice\Commands\ApplyPurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceApplyPaymentService;
use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use App\Modules\SupplierAdvance\Services\SupplierAdvancePaymentSyncService;
use App\Modules\SupplierPayment\Commands\PostSupplierPaymentApplicationCommand;
use App\Modules\SupplierPayment\Commands\SupplierPaymentApplicationData;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El momento en que el pago deja de ser un papel y mueve dinero.
 *
 * Confirmarlo abona cada factura del reparto y baja lo que se le debe al
 * proveedor; anularlo hace exactamente lo contrario. Mientras el pago está en
 * borrador su reparto ya está escrito, pero ningún saldo se ha movido: es el
 * mismo trato que la factura le da a sus líneas.
 *
 * El pago espejo de un anticipo es el caso aparte: no reparte nada entre
 * facturas, así que confirmarlo o anularlo no mueve saldos aquí sino el estado
 * del anticipo que lo generó (`docs/compras.md` §5.1).
 *
 * Un pago que no saca dinero —`payment_method` `advance` o `credit_note`— sí
 * reparte: lo que gasta es el crédito que ya se tenía con el proveedor, y sus
 * filas viajan con el `source_type` de ese crédito en vez de con el suyo
 * (`docs/compras.md` §6.3).
 *
 * Lo que sale y no se reparte tampoco se queda en el aire: al confirmar se
 * convierte en un anticipo `ANP` ya confirmado
 * (`SupplierPaymentSurplusService`, `docs/compras.md` §6.2).
 */
class SupplierPaymentPostingService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
        private readonly SupplierPaymentOriginService $origin,
        private readonly PurchaseInvoiceRepositoryInterface $purchaseInvoices,
        private readonly PurchaseInvoiceApplyPaymentService $applyToInvoice,
        private readonly SupplierApplyBalanceService $applyToSupplier,
        private readonly SupplierAdvancePaymentSyncService $advances,
        private readonly SupplierPaymentCreditSourceService $creditSources,
        private readonly SupplierPaymentSurplusService $surplus,
    ) {}

    /**
     * Abona el reparto. Se vuelve a comprobar contra el saldo vivo de cada
     * factura: entre la captura y la confirmación otro documento pudo haberse
     * llevado lo que este pago pensaba abonar.
     */
    public function post(SupplierPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            /** El pago del anticipo no abona facturas: lo entrega. */
            if ($payment->origin_type === SupplierPayment::ORIGIN_ADVANCE) {
                $this->advances->confirm($payment);

                return;
            }

            $applications = $this->repository->activeApplications($payment);

            $this->origin->guardApplications(
                array_map(
                    static fn (SupplierPaymentApplication $row): SupplierPaymentApplicationData => new SupplierPaymentApplicationData(
                        purchaseInvoiceId: $row->purchase_invoice_id,
                        appliedAmount: round((float) $row->applied_amount, 2),
                    ),
                    $applications,
                ),
                $payment->supplier_id,
                $payment->company_id,
            );

            $applied = 0.0;

            foreach ($applications as $application) {
                $amount = round((float) $application->applied_amount, 2);

                $this->applyToInvoice->execute(new ApplyPurchaseInvoicePaymentCommand(
                    companyId: $payment->company_id,
                    purchaseInvoiceId: $application->purchase_invoice_id,
                    appliedDelta: $amount,
                ));

                $this->repository->postApplication($application, new PostSupplierPaymentApplicationCommand(
                    appliedAt: now()->toDateTimeString(),
                    exchangeRate: (float) $payment->exchange_rate,
                    exchangeDifference: $this->exchangeDifference($payment, $application->purchase_invoice_id, $amount),
                ));

                $applied += $amount;
            }

            $applied = round($applied, 2);

            $this->moveSupplierBalance($payment, -$applied);
            $this->creditSources->consume($payment, $applied);

            /** Lo que salió y no cancela ninguna factura queda como anticipo. */
            $this->surplus->capture($payment);
        });
    }

    /**
     * Deshace el abono: las aplicaciones quedan en `reversed`, cada factura
     * recupera su saldo y el proveedor vuelve a deber lo que este pago le
     * había cancelado. Ninguna fila se borra.
     */
    public function reverse(SupplierPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            if ($payment->origin_type === SupplierPayment::ORIGIN_ADVANCE) {
                $this->advances->revert($payment);

                return;
            }

            $applied = 0.0;

            foreach ($this->repository->activeApplications($payment) as $application) {
                $amount = round((float) $application->applied_amount, 2);

                $this->applyToInvoice->execute(new ApplyPurchaseInvoicePaymentCommand(
                    companyId: $payment->company_id,
                    purchaseInvoiceId: $application->purchase_invoice_id,
                    appliedDelta: -$amount,
                ));

                $this->repository->reverseApplication($application);

                $applied += $amount;
            }

            $applied = round($applied, 2);

            $this->surplus->release($payment);

            $this->moveSupplierBalance($payment, $applied);
            $this->creditSources->release($payment, $applied);
        });
    }

    /**
     * Anular un pago que nunca llegó a abonar no revierte ningún saldo, pero sí
     * devuelve a su anticipo el derecho a corregirse: vuelve a `draft` y puede
     * aprobarse de nuevo, conservando su `code`.
     */
    public function discard(SupplierPayment $payment): void
    {
        if ($payment->origin_type !== SupplierPayment::ORIGIN_ADVANCE) {
            return;
        }

        $this->advances->revert($payment);
    }

    /**
     * Lo que sobra o falta en bolívares entre la tasa con la que la factura
     * congeló su deuda y la que el pago congeló al cancelarla. En la moneda
     * del documento la deuda queda saldada exacta; en bolívares no.
     */
    private function exchangeDifference(SupplierPayment $payment, string $invoiceId, float $amount): float
    {
        $invoice = $this->purchaseInvoices->findById($invoiceId, $payment->company_id);

        if (! $invoice instanceof PurchaseInvoice) {
            return 0.0;
        }

        return round($amount * ((float) $payment->exchange_rate - (float) $invoice->exchange_rate), 2);
    }

    /**
     * Un pago sin reparto no mueve nada: no hay deuda que cancelar.
     *
     * Pagar con una nota de crédito tampoco la mueve: la nota ya bajó el
     * `current_balance` del proveedor al confirmarse, y volver a bajarlo aquí
     * cancelaría la misma deuda dos veces. El anticipo sí, porque quedó como
     * crédito a favor y no como pago de una factura.
     */
    private function moveSupplierBalance(SupplierPayment $payment, float $delta): void
    {
        if ($delta === 0.0 || $payment->payment_method === 'credit_note') {
            return;
        }

        $this->applyToSupplier->execute(new ApplySupplierBalanceCommand(
            companyId: $payment->company_id,
            supplierId: $payment->supplier_id,
            currentBalanceDelta: $delta,
        ));
    }
}
