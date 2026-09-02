<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\PurchaseCreditNote\Commands\UpdateStatusPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\WritePurchaseCreditNoteAppliedCommand;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\WriteSupplierAdvanceAppliedCommand;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El crédito que respalda un pago que no saca dinero.
 *
 * Pagar con `advance` o con `credit_note` no es desembolsar: es gastar un saldo
 * a favor que ya se tiene con el proveedor. Este servicio comprueba que ese
 * crédito exista, sea del mismo proveedor, esté confirmado y alcance, y lo
 * consume —o lo devuelve— cuando el pago se confirma o se anula.
 *
 * El anticipo consume además el `advance_balance` del proveedor; la nota de
 * crédito no, porque bajó su `current_balance` cuando se confirmó y nunca fue
 * crédito a favor.
 */
class SupplierPaymentCreditSourceService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $advances,
        private readonly PurchaseCreditNoteRepositoryInterface $creditNotes,
        private readonly SupplierPaymentApplyCreditService $credits,
    ) {}

    /**
     * El crédito indicado sirve para este pago y alcanza para lo que se quiere
     * repartir. `$exceptApplied` es lo que este mismo pago ya tenía tomado del
     * crédito, que no compite consigo mismo al editarse.
     *
     * @throws ValidationException
     */
    public function guard(
        string $paymentMethod,
        ?string $creditSourceId,
        string $supplierId,
        ?string $companyId,
        float $applied,
        float $exceptApplied = 0,
    ): void {
        if (! isset(SupplierPayment::CREDIT_METHODS[$paymentMethod])) {
            if (filled($creditSourceId)) {
                throw ValidationException::withMessages([
                    'credit_source_id' => 'Un pago con dinero de por medio no gasta ningún crédito.',
                ]);
            }

            return;
        }

        if (blank($creditSourceId)) {
            throw ValidationException::withMessages([
                'credit_source_id' => 'Indica de qué anticipo o nota de crédito sale el pago.',
            ]);
        }

        $source = $this->source($paymentMethod, (string) $creditSourceId, $companyId);

        if ($source === null) {
            throw ValidationException::withMessages([
                'credit_source_id' => 'El crédito indicado no existe en esta empresa.',
            ]);
        }

        if ($source->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                'credit_source_id' => 'El crédito pertenece a otro proveedor: un pago no cruza proveedores.',
            ]);
        }

        if (! in_array($source->status, $this->applicableStatuses($paymentMethod), true)) {
            throw ValidationException::withMessages([
                'credit_source_id' => "El crédito {$source->code} no está disponible en su estado actual.",
            ]);
        }

        $available = round((float) $source->balance + $exceptApplied, 2);

        if (round($applied, 2) > $available) {
            throw ValidationException::withMessages([
                'credit_source_id' => "El crédito {$source->code} solo tiene {$available} disponible.",
            ]);
        }
    }

    /** Gasta el crédito del pago que se acaba de confirmar. */
    public function consume(SupplierPayment $payment, float $applied): void
    {
        $this->move($payment, round($applied, 2));
    }

    /** Devuelve el crédito del pago que se acaba de anular. */
    public function release(SupplierPayment $payment, float $applied): void
    {
        $this->move($payment, -round($applied, 2));
    }

    /**
     * Mueve lo aplicado del crédito y, si es un anticipo, el saldo a favor con
     * el proveedor. Un pago con dinero de por medio no pasa por aquí.
     */
    private function move(SupplierPayment $payment, float $delta): void
    {
        if (! $payment->fundedByCredit() || $delta === 0.0) {
            return;
        }

        DB::transaction(function () use ($payment, $delta): void {
            $source = $this->lockedSource($payment);

            if ($source === null) {
                return;
            }

            $applied = max(round((float) $source->applied_amount + $delta, 2), 0);

            $source instanceof SupplierAdvance
                ? $this->writeAdvance($source, $applied)
                : $this->writeCreditNote($source, $applied);

            /** El anticipo era saldo a favor con el proveedor: gastarlo lo consume. */
            if ($source instanceof SupplierAdvance) {
                $this->credits->moveAdvanceBalance($payment->company_id, $payment->supplier_id, -$delta);
            }
        });
    }

    /** @return SupplierAdvance|PurchaseCreditNote|null */
    private function source(string $paymentMethod, string $id, ?string $companyId): ?Model
    {
        return $paymentMethod === 'advance'
            ? $this->advances->findById($id, $companyId)
            : $this->creditNotes->findById($id, $companyId);
    }

    /** @return SupplierAdvance|PurchaseCreditNote|null */
    private function lockedSource(SupplierPayment $payment): ?Model
    {
        $id = (string) $payment->credit_source_id;

        return $payment->payment_method === 'advance'
            ? $this->advances->lockById($id, $payment->company_id)
            : $this->creditNotes->lockById($id, $payment->company_id);
    }

    /**
     * Desde qué estados se puede gastar el crédito. Un anticipo a medio gastar
     * sigue sirviendo; una nota solo desde confirmada, porque aplicarla entera
     * la agota y la deja `completed`.
     *
     * @return array<int, string>
     */
    private function applicableStatuses(string $paymentMethod): array
    {
        return $paymentMethod === 'advance'
            ? ['confirmed', 'partial']
            : ['confirmed'];
    }

    /**
     * Un anticipo a medio gastar está `partial`; agotado, `completed`; devuelto
     * entero vuelve a `confirmed`.
     */
    private function writeAdvance(SupplierAdvance $advance, float $applied): void
    {
        $amount = round((float) $advance->amount, 2);
        $balance = round($amount - $applied, 2);

        $this->advances->writeApplied($advance, new WriteSupplierAdvanceAppliedCommand(
            appliedAmount: $applied,
            balance: $balance,
        ));

        $status = match (true) {
            $balance <= 0 => 'completed',
            $applied > 0 => 'partial',
            default => 'confirmed',
        };

        if ($status !== $advance->status) {
            $this->advances->updateStatus($advance, new UpdateStatusSupplierAdvanceCommand($status));
        }
    }

    /** Una nota agotada está `completed`; devuelta entera vuelve a `confirmed`. */
    private function writeCreditNote(PurchaseCreditNote $note, float $applied): void
    {
        $total = round((float) $note->total, 2);
        $balance = round($total - $applied, 2);

        $this->creditNotes->writeApplied($note, new WritePurchaseCreditNoteAppliedCommand(
            appliedAmount: $applied,
            balance: $balance,
        ));

        $status = $balance <= 0 ? 'completed' : 'confirmed';

        if ($status !== $note->status) {
            $this->creditNotes->updateStatus($note, new UpdateStatusPurchaseCreditNoteCommand($status));
        }
    }
}
