<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\Client\Commands\ApplyClientBalanceCommand;
use App\Modules\Client\Services\ClientApplyBalanceService;
use App\Modules\ClientAdvance\Services\ClientAdvanceCollectionSyncService;
use App\Modules\ClientCollection\Commands\ClientCollectionApplicationData;
use App\Modules\ClientCollection\Commands\PostClientCollectionApplicationCommand;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\SalesInvoice\Commands\ApplySalesInvoiceCollectionCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\SalesInvoice\Services\SalesInvoiceApplyCollectionService;
use Illuminate\Support\Facades\DB;

/**
 * El momento en que el cobro deja de ser un papel y mueve dinero.
 *
 * Confirmarlo abona cada factura del reparto y baja lo que el cliente nos debe;
 * anularlo hace exactamente lo contrario. Mientras el cobro está en borrador su
 * reparto ya está escrito, pero ningún saldo se ha movido: es el mismo trato
 * que la factura le da a sus líneas.
 *
 * El cobro espejo de un anticipo es el caso aparte: no reparte nada entre
 * facturas, así que confirmarlo o anularlo no mueve saldos aquí sino el estado
 * del anticipo que lo generó (`docs/ventas.md` §5.1).
 *
 * Un cobro que no trae dinero —`payment_method` `advance` o `credit_note`— sí
 * reparte: lo que gasta es el crédito que el cliente ya tenía, y sus filas
 * viajan con el `source_type` de ese crédito en vez de con el suyo
 * (`docs/ventas.md` §6.3).
 *
 * Lo que entra y no se reparte tampoco se queda en el aire: al confirmar se
 * convierte en un anticipo `ANC` ya confirmado
 * (`ClientCollectionSurplusService`, `docs/ventas.md` §6.2).
 */
class ClientCollectionPostingService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
        private readonly ClientCollectionOriginService $origin,
        private readonly SalesInvoiceRepositoryInterface $salesInvoices,
        private readonly SalesInvoiceApplyCollectionService $applyToInvoice,
        private readonly ClientApplyBalanceService $applyToClient,
        private readonly ClientAdvanceCollectionSyncService $advances,
        private readonly ClientCollectionCreditSourceService $creditSources,
        private readonly ClientCollectionSurplusService $surplus,
    ) {}

    /**
     * Abona el reparto. Se vuelve a comprobar contra el saldo vivo de cada
     * factura: entre la captura y la confirmación otro documento pudo haberse
     * llevado lo que este cobro pensaba abonar.
     */
    public function post(ClientCollection $collection): void
    {
        DB::transaction(function () use ($collection): void {
            /** El cobro del anticipo no abona facturas: lo recibe. */
            if ($collection->origin_type === ClientCollection::ORIGIN_ADVANCE) {
                $this->advances->confirm($collection);

                return;
            }

            $applications = $this->repository->activeApplications($collection);

            $this->origin->guardApplications(
                array_map(
                    static fn (ClientCollectionApplication $row): ClientCollectionApplicationData => new ClientCollectionApplicationData(
                        salesInvoiceId: $row->sales_invoice_id,
                        appliedAmount: round((float) $row->applied_amount, 2),
                    ),
                    $applications,
                ),
                $collection->client_id,
                $collection->company_id,
            );

            $applied = 0.0;

            foreach ($applications as $application) {
                $amount = round((float) $application->applied_amount, 2);

                $this->applyToInvoice->execute(new ApplySalesInvoiceCollectionCommand(
                    companyId: $collection->company_id,
                    salesInvoiceId: $application->sales_invoice_id,
                    appliedDelta: $amount,
                ));

                $this->repository->postApplication($application, new PostClientCollectionApplicationCommand(
                    appliedAt: now()->toDateTimeString(),
                    exchangeRate: (float) $collection->exchange_rate,
                    exchangeDifference: $this->exchangeDifference($collection, $application->sales_invoice_id, $amount),
                ));

                $applied += $amount;
            }

            $applied = round($applied, 2);

            $this->moveClientBalance($collection, -$applied);
            $this->creditSources->consume($collection, $applied);

            /** Lo que entró y no cancela ninguna factura queda como anticipo. */
            $this->surplus->capture($collection);
        });
    }

    /**
     * Deshace el abono: las aplicaciones quedan en `reversed`, cada factura
     * recupera su saldo y el cliente vuelve a deber lo que este cobro le había
     * cancelado. Ninguna fila se borra.
     */
    public function reverse(ClientCollection $collection): void
    {
        DB::transaction(function () use ($collection): void {
            if ($collection->origin_type === ClientCollection::ORIGIN_ADVANCE) {
                $this->advances->revert($collection);

                return;
            }

            $applied = 0.0;

            foreach ($this->repository->activeApplications($collection) as $application) {
                $amount = round((float) $application->applied_amount, 2);

                $this->applyToInvoice->execute(new ApplySalesInvoiceCollectionCommand(
                    companyId: $collection->company_id,
                    salesInvoiceId: $application->sales_invoice_id,
                    appliedDelta: -$amount,
                ));

                $this->repository->reverseApplication($application);

                $applied += $amount;
            }

            $applied = round($applied, 2);

            $this->surplus->release($collection);

            $this->moveClientBalance($collection, $applied);
            $this->creditSources->release($collection, $applied);
        });
    }

    /**
     * Anular un cobro que nunca llegó a abonar no revierte ningún saldo, pero sí
     * devuelve a su anticipo el derecho a corregirse: vuelve a `draft` y puede
     * aprobarse de nuevo, conservando su `code`.
     */
    public function discard(ClientCollection $collection): void
    {
        if ($collection->origin_type !== ClientCollection::ORIGIN_ADVANCE) {
            return;
        }

        $this->advances->revert($collection);
    }

    /**
     * Lo que sobra o falta en bolívares entre la tasa con la que la factura
     * congeló su deuda y la que el cobro congeló al cancelarla. En la moneda
     * del documento la deuda queda saldada exacta; en bolívares no.
     */
    private function exchangeDifference(ClientCollection $collection, string $invoiceId, float $amount): float
    {
        $invoice = $this->salesInvoices->findById($invoiceId, $collection->company_id);

        if (! $invoice instanceof SalesInvoice) {
            return 0.0;
        }

        return round($amount * ((float) $collection->exchange_rate - (float) $invoice->exchange_rate), 2);
    }

    /**
     * Un cobro sin reparto no mueve nada: no hay deuda que cancelar.
     *
     * Cobrar con una nota de crédito tampoco la mueve: la nota ya bajó el
     * `current_balance` del cliente al confirmarse, y volver a bajarlo aquí
     * cancelaría la misma deuda dos veces. El anticipo sí, porque entró como
     * crédito a favor y no como pago.
     */
    private function moveClientBalance(ClientCollection $collection, float $delta): void
    {
        if ($delta === 0.0 || $collection->payment_method === 'credit_note') {
            return;
        }

        $this->applyToClient->execute(new ApplyClientBalanceCommand(
            companyId: $collection->company_id,
            clientId: $collection->client_id,
            currentBalanceDelta: $delta,
        ));
    }
}
