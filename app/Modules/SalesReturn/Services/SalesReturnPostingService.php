<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\SalesInvoice\Commands\ApplySalesInvoiceReturnCommand;
use App\Modules\SalesInvoice\Services\SalesInvoiceApplyReturnService;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El momento en que la devolución deja de ser un papel y consume el cupo de la
 * factura que la origina.
 *
 * Confirmarla apunta lo devuelto en cada línea facturada, que es lo que habilita
 * la nota de crédito; anularla libera ese cupo. Mientras está en borrador sus
 * líneas ya están escritas, pero nada se ha consumido.
 *
 * **No mueve inventario.** La devolución es el acuerdo con el cliente, no el
 * reingreso de la mercancía: ese lo asienta la Entrada que la recibe, al costo
 * original de la venta. Lo que vuelve para destruirse (`scrap`) no reingresa a
 * ninguna bodega y su pérdida se registra por Ajuste, pero cuenta igual aquí:
 * al cliente se le acredita lo que devolvió, esté o no en estado de volver al
 * almacén.
 */
class SalesReturnPostingService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
        private readonly SalesInvoiceApplyReturnService $applyToInvoiceLine,
    ) {}

    /** Apunta lo devuelto en la factura de origen. */
    public function post(SalesReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            foreach ($this->repository->activeLines($return) as $line) {
                $this->moveInvoiceLine($return, $line, round((float) $line->quantity, 4));
            }
        });
    }

    /** Deshace lo apuntado: la factura recupera el cupo devuelto. */
    public function reverse(SalesReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            foreach ($this->repository->activeLines($return) as $line) {
                $this->moveInvoiceLine($return, $line, -round((float) $line->quantity, 4));
            }
        });
    }

    /**
     * Apunta —o libera— lo devuelto en la línea de la factura de origen. Una
     * devolución sin factura no tiene dónde apuntarlo.
     */
    private function moveInvoiceLine(SalesReturn $return, SalesReturnLine $line, float $delta): void
    {
        if (blank($line->sales_invoice_line_id) || $delta === 0.0) {
            return;
        }

        $this->applyToInvoiceLine->execute(new ApplySalesInvoiceReturnCommand(
            companyId: $return->company_id,
            salesInvoiceLineId: $line->sales_invoice_line_id,
            returnedDelta: $delta,
        ));
    }
}
