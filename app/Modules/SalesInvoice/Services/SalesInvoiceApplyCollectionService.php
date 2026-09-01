<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Commands\ApplySalesInvoiceCollectionCommand;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceCollectionCommand;
use App\Modules\SalesInvoice\Exceptions\SalesInvoiceNotFoundException;
use App\Modules\SalesInvoice\Exceptions\SalesInvoiceOvercollectedException;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `paid_amount`, `balance` y `payment_status` de una
 * factura de venta después de emitida.
 *
 * Lo llaman los cobros, los anticipos y las notas de crédito cuando abonan la
 * factura, y otra vez con el signo contrario cuando esa aplicación se revierte.
 * La factura no se recalcula: lo cobrado no puede pasarse de su total ni bajar
 * de cero.
 *
 * La transacción es anidable: llamado desde el documento que aplica se suma a
 * la transacción abierta como savepoint.
 */
class SalesInvoiceApplyCollectionService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(ApplySalesInvoiceCollectionCommand $command): SalesInvoice
    {
        return DB::transaction(function () use ($command): SalesInvoice {
            $invoice = $this->repository->lockById($command->salesInvoiceId, $command->companyId);

            if ($invoice === null) {
                throw new SalesInvoiceNotFoundException;
            }

            $paid = round((float) $invoice->paid_amount + $command->appliedDelta, 2);
            $total = round((float) $invoice->total, 2);

            if ($paid < 0 || $paid > $total) {
                throw new SalesInvoiceOvercollectedException;
            }

            return $this->repository->writeCollection($invoice, new WriteSalesInvoiceCollectionCommand(
                paidAmount: $paid,
                balance: round($total - $paid, 2),
                paymentStatus: $this->paymentStatus($invoice, $paid, $total),
            ));
        });
    }

    /**
     * Cobrada es `paid`; con algo abonado, `partial`; sin nada, `pending`. Una
     * factura vencida que sigue debiendo conserva su `overdue`: el día en que
     * venció no lo borra un abono parcial.
     */
    private function paymentStatus(SalesInvoice $invoice, float $paid, float $total): string
    {
        if ($paid >= $total && $total > 0) {
            return 'paid';
        }

        /** Vence al final de su día: una factura que vence hoy todavía no está vencida. */
        if ($invoice->due_date !== null && $invoice->due_date->startOfDay()->lt(now()->startOfDay())) {
            return 'overdue';
        }

        return $paid > 0 ? 'partial' : 'pending';
    }
}
