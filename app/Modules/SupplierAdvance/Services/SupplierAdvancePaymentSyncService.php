<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Exceptions\SupplierAdvanceNotFoundException;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El lado del anticipo de su pago espejo.
 *
 * Lo llama el módulo de pagos en los dos únicos momentos que importan:
 * confirmar el pago —el dinero salió, el anticipo queda entregado y el
 * proveedor nos debe un crédito— y anularlo —el anticipo vuelve a borrador
 * para corregirse y aprobarse de nuevo, conservando su `code`—.
 *
 * La transacción es anidable: llamada desde el pago se suma a la suya como
 * savepoint.
 */
class SupplierAdvancePaymentSyncService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $repository,
        private readonly SupplierApplyBalanceService $supplierBalances,
    ) {}

    /**
     * El pago se confirmó: el anticipo pasa a `confirmed` y solo entonces suma
     * al crédito a favor del proveedor.
     *
     * @throws ValidationException si el anticipo no estaba esperando su pago.
     */
    public function confirm(SupplierPayment $payment): SupplierAdvance
    {
        return DB::transaction(function () use ($payment): SupplierAdvance {
            $advance = $this->lockedAdvance($payment);

            if ($advance->status !== 'pending_confirmation') {
                throw ValidationException::withMessages([
                    'status' => "El anticipo {$advance->code} no está esperando su pago.",
                ]);
            }

            $this->repository->updateStatus($advance, new UpdateStatusSupplierAdvanceCommand('confirmed'));

            $this->moveAdvanceBalance($advance, round((float) $advance->amount, 2));

            return $this->repository->findOrFail($advance->id, $advance->company_id);
        });
    }

    /**
     * El pago se anuló: el anticipo vuelve a borrador. Si ya estaba entregado
     * también se le quita al proveedor el crédito que le había dado.
     *
     * @throws ValidationException si el anticipo ya se aplicó a alguna factura.
     */
    public function revert(SupplierPayment $payment): ?SupplierAdvance
    {
        return DB::transaction(function () use ($payment): ?SupplierAdvance {
            $advance = $this->lockedAdvance($payment);

            /** Un anticipo agotado o anulado ya no depende de este pago. */
            if (! in_array($advance->status, ['pending_confirmation', 'confirmed'], true)) {
                return null;
            }

            if (round((float) $advance->applied_amount, 2) > 0) {
                throw ValidationException::withMessages([
                    'status' => "El anticipo {$advance->code} ya se aplicó a facturas: revierte esas aplicaciones primero.",
                ]);
            }

            $wasDelivered = $advance->status === 'confirmed';

            $this->repository->updateStatus($advance, new UpdateStatusSupplierAdvanceCommand('draft'));

            if ($wasDelivered) {
                $this->moveAdvanceBalance($advance, -round((float) $advance->amount, 2));
            }

            return $this->repository->findOrFail($advance->id, $advance->company_id);
        });
    }

    private function lockedAdvance(SupplierPayment $payment): SupplierAdvance
    {
        $advance = $this->repository->lockById((string) $payment->origin_id, $payment->company_id);

        if ($advance === null) {
            throw new SupplierAdvanceNotFoundException;
        }

        return $advance;
    }

    /** El crédito a favor del proveedor lo mueve siempre su propio servicio. */
    private function moveAdvanceBalance(SupplierAdvance $advance, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->supplierBalances->execute(new ApplySupplierBalanceCommand(
            companyId: $advance->company_id,
            supplierId: $advance->supplier_id,
            advanceBalanceDelta: $delta,
        ));
    }
}
