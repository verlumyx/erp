<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\Client\Commands\ApplyClientBalanceCommand;
use App\Modules\Client\Services\ClientApplyBalanceService;
use App\Modules\ClientCollection\Commands\ApplyClientCreditCommand;
use App\Modules\ClientCollection\Commands\WriteClientCollectionApplicationCommand;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\SalesInvoice\Commands\ApplySalesInvoiceCollectionCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\SalesInvoice\Services\SalesInvoiceApplyCollectionService;
use Illuminate\Support\Facades\DB;

/**
 * El camino por el que un crédito que no es dinero —un anticipo `ANC` o una
 * nota de crédito `NCC`— abona una factura de venta.
 *
 * El cobro tiene el suyo (`ClientCollectionPostingService`): captura su reparto
 * en borrador y lo confirma después. Estos otros no: el crédito que los
 * respalda ya existe y ya bajó el saldo del cliente cuando el documento se
 * confirmó, así que la aplicación nace abonada y lo único que hace aquí es
 * repartirla entre facturas.
 *
 * Por eso **no** mueve `current_balance` del cliente al aplicar: la nota ya lo
 * bajó al confirmarse y el anticipo entró como crédito a favor, no como deuda.
 * Lo que sí mueve es el `advance_balance` cuando el crédito que se gasta es un
 * anticipo: ese saldo a favor se consume.
 *
 * La transacción es anidable: llamado desde el documento que aplica se suma a
 * la suya como savepoint.
 */
class ClientCollectionApplyCreditService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
        private readonly SalesInvoiceRepositoryInterface $salesInvoices,
        private readonly SalesInvoiceApplyCollectionService $applyToInvoice,
        private readonly ClientApplyBalanceService $applyToClient,
    ) {}

    /** Abona la factura con el crédito indicado y deja escrita la fila. */
    public function apply(ApplyClientCreditCommand $command): ClientCollectionApplication
    {
        return DB::transaction(function () use ($command): ClientCollectionApplication {
            $amount = round($command->appliedAmount, 2);

            $this->applyToInvoice->execute(new ApplySalesInvoiceCollectionCommand(
                companyId: $command->companyId,
                salesInvoiceId: $command->salesInvoiceId,
                appliedDelta: $amount,
            ));

            return $this->repository->writeApplication(new WriteClientCollectionApplicationCommand(
                companyId: $command->companyId,
                salesInvoiceId: $command->salesInvoiceId,
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

                $this->applyToInvoice->execute(new ApplySalesInvoiceCollectionCommand(
                    companyId: $companyId,
                    salesInvoiceId: $application->sales_invoice_id,
                    appliedDelta: -$amount,
                ));

                $this->repository->reverseApplication($application);

                $reverted += $amount;
            }

            return round($reverted, 2);
        });
    }

    /** Consume —o devuelve— el crédito a favor del cliente. */
    public function moveAdvanceBalance(?string $companyId, string $clientId, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->applyToClient->execute(new ApplyClientBalanceCommand(
            companyId: $companyId,
            clientId: $clientId,
            advanceBalanceDelta: $delta,
        ));
    }

    /**
     * Lo que sobra o falta en bolívares entre la tasa con la que la factura
     * congeló su deuda y la que el crédito congeló al cancelarla.
     */
    private function exchangeDifference(ApplyClientCreditCommand $command, float $amount): float
    {
        $invoice = $this->salesInvoices->findById($command->salesInvoiceId, $command->companyId);

        if (! $invoice instanceof SalesInvoice) {
            return 0.0;
        }

        return round($amount * ($command->exchangeRate - (float) $invoice->exchange_rate), 2);
    }
}
