<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use Illuminate\Support\Facades\DB;

/**
 * El momento en que la nota deja de ser un papel y baja lo que se le debe al
 * proveedor.
 *
 * Confirmarla descarga su cuenta por pagar; anularla se la devuelve. Mientras
 * está en borrador sus líneas ya están escritas, pero ningún saldo se ha
 * movido.
 *
 * **No mueve inventario.** La mercancía que la motiva —una devolución al
 * proveedor— sale con su Despacho, y es ese despacho el que escribe el kardex.
 * La nota solo dice cuánto crédito genera el acuerdo.
 */
class PurchaseCreditNotePostingService
{
    public function __construct(
        private readonly SupplierApplyBalanceService $applyToSupplier,
    ) {}

    /** Acredita la nota: baja la deuda con el proveedor. */
    public function post(PurchaseCreditNote $note): void
    {
        DB::transaction(function () use ($note): void {
            $this->moveSupplierBalance($note, -round((float) $note->total, 2));
        });
    }

    /** Deshace el crédito: la deuda con el proveedor vuelve a subir. */
    public function reverse(PurchaseCreditNote $note): void
    {
        DB::transaction(function () use ($note): void {
            $this->moveSupplierBalance($note, round((float) $note->total, 2));
        });
    }

    /** Descarga (o devuelve) la cuenta por pagar del proveedor. */
    private function moveSupplierBalance(PurchaseCreditNote $note, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->applyToSupplier->execute(new ApplySupplierBalanceCommand(
            companyId: $note->company_id,
            supplierId: $note->supplier_id,
            currentBalanceDelta: $delta,
        ));
    }
}
