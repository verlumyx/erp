<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceMarkOverdueService;
use App\Modules\SalesInvoice\Services\SalesInvoiceMarkOverdueService;
use Illuminate\Console\Command;

/**
 * El vencimiento diario de las facturas abiertas.
 *
 * `payment_status` solo se recalcula cuando entra un cobro o un pago, así que
 * una factura que vence sin que nadie la toque se quedaría en `pending` para
 * siempre. Este comando la marca `overdue` comparando `due_date` con la fecha
 * de corte, sobre las `FVE` y las `FCO` que todavía deben algo
 * (`docs/compras.md` §3.2).
 *
 * Corre sobre todas las empresas: el vencimiento no es una decisión de nadie,
 * es el calendario.
 */
class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue
                            {--date= : Fecha de corte (Y-m-d). Por defecto, hoy.}
                            {--company= : Limitar a una empresa.}';

    protected $description = 'Marca como vencidas las facturas de venta y de compra que pasaron su fecha con saldo.';

    public function handle(
        SalesInvoiceMarkOverdueService $salesInvoices,
        PurchaseInvoiceMarkOverdueService $purchaseInvoices,
    ): int {
        $date = $this->option('date') ?: now()->toDateString();
        $company = $this->option('company') ?: null;

        $sales = $salesInvoices->execute($date, $company);
        $purchases = $purchaseInvoices->execute($date, $company);

        $this->info("Vencidas al {$date}: {$sales} de venta y {$purchases} de compra.");

        return self::SUCCESS;
    }
}
