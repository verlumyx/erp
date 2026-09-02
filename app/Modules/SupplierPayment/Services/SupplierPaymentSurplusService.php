<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use App\Modules\SupplierAdvance\Commands\CreateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\SearchSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El dinero que salió y no cancela ninguna factura.
 *
 * Un pago puede entregar más de lo que reparte —se paga de más, o se paga a
 * cuenta sin decir contra qué—. Ese `unapplied_amount` no se queda flotando en
 * la cabecera del pago: al confirmarlo se convierte en un anticipo `ANP` **ya
 * confirmado**, porque el dinero ya salió con este pago y no tiene que volver a
 * aprobarse ni generar otro `PGP` (`docs/compras.md` §6.2).
 *
 * Quedan exentos dos casos:
 *
 * - El pago espejo de un anticipo (`origin_type = 'advance'`): ahí
 *   `unapplied_amount = amount` por definición y el anticipo ya existe.
 * - El pago que no saca dinero (`payment_method` `advance` o `credit_note`):
 *   lo que no se reparte sigue disponible en el crédito que lo respalda, y
 *   volver a guardarlo como anticipo lo contaría dos veces.
 */
class SupplierPaymentSurplusService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $advances,
        private readonly SupplierApplyBalanceService $supplierBalances,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * Convierte el excedente del pago en crédito a favor con el proveedor.
     * Devuelve el anticipo generado, o `null` si no había nada que guardar.
     */
    public function capture(SupplierPayment $payment): ?SupplierAdvance
    {
        if (! $this->generatesSurplus($payment)) {
            return null;
        }

        $surplus = round((float) $payment->unapplied_amount, 2);

        if ($surplus <= 0) {
            return null;
        }

        return DB::transaction(function () use ($payment, $surplus): SupplierAdvance {
            /** Un pago genera un solo anticipo: confirmarlo dos veces no lo duplica. */
            $existing = $this->generatedAdvance($payment);

            if ($existing instanceof SupplierAdvance) {
                return $existing;
            }

            $id = (string) Str::uuid7();

            $this->advances->create(
                new CreateSupplierAdvanceCommand(
                    id: $id,
                    companyId: (string) $payment->company_id,
                    supplierId: $payment->supplier_id,
                    advanceDate: $payment->payment_date->toDateString(),
                    amount: $surplus,
                    createdBy: (string) $payment->created_by,
                    originPaymentId: $payment->id,
                    paymentMethod: $this->advanceMethod($payment),
                    reference: $payment->reference,
                    bankAccount: $payment->bank_account,
                    currency: $payment->currency,
                    notes: "Excedente del pago {$payment->code}.",
                ),
                $this->frozenRates($payment),
            );

            $advance = $this->advances->findOrFail($id, $payment->company_id);

            /** Nace entregado: el dinero salió con el pago que lo generó. */
            $this->advances->updateStatus($advance, new UpdateStatusSupplierAdvanceCommand('confirmed'));

            $this->moveAdvanceBalance($payment, $surplus);

            return $this->advances->findOrFail($id, $payment->company_id);
        });
    }

    /**
     * Anula el anticipo que el pago había generado: el dinero que lo respaldaba
     * se va con el pago.
     *
     * @throws ValidationException si ese anticipo ya se aplicó a facturas.
     */
    public function release(SupplierPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $advance = $this->generatedAdvance($payment);

            if (! $advance instanceof SupplierAdvance || $advance->status === 'cancelled') {
                return;
            }

            if (round((float) $advance->applied_amount, 2) > 0) {
                throw ValidationException::withMessages([
                    'status' => "El excedente de este pago quedó en el anticipo {$advance->code}, que ya se aplicó a facturas: revierte esas aplicaciones primero.",
                ]);
            }

            $wasDelivered = in_array($advance->status, ['confirmed', 'partial'], true);

            $this->advances->updateStatus($advance, new UpdateStatusSupplierAdvanceCommand('cancelled'));

            if ($wasDelivered) {
                $this->moveAdvanceBalance($payment, -round((float) $advance->amount, 2));
            }
        });
    }

    /**
     * Solo el pago que saca dinero de verdad y arranca de un proveedor o de una
     * factura genera anticipo por lo que le sobra.
     */
    private function generatesSurplus(SupplierPayment $payment): bool
    {
        if ($payment->origin_type === SupplierPayment::ORIGIN_ADVANCE) {
            return false;
        }

        return ! isset(SupplierPayment::CREDIT_METHODS[$payment->payment_method]);
    }

    /** El anticipo que este pago generó, si llegó a generar alguno. */
    private function generatedAdvance(SupplierPayment $payment): ?SupplierAdvance
    {
        $result = $this->advances->search(new SearchSupplierAdvanceCommand(
            filters: ['origin_payment_id' => $payment->id],
            limit: 1,
            companyId: $payment->company_id,
        ));

        return $result['data'][0] ?? null;
    }

    /**
     * La forma de pago que el anticipo copia. `ANP` no admite las dos que no
     * sacan dinero, pero esas ya quedaron exentas más arriba.
     */
    private function advanceMethod(SupplierPayment $payment): string
    {
        return in_array($payment->payment_method, SupplierAdvance::PAYMENT_METHODS, true)
            ? $payment->payment_method
            : 'other';
    }

    /** El crédito a favor con el proveedor lo mueve siempre su propio servicio. */
    private function moveAdvanceBalance(SupplierPayment $payment, float $delta): void
    {
        $this->supplierBalances->execute(new ApplySupplierBalanceCommand(
            companyId: $payment->company_id,
            supplierId: $payment->supplier_id,
            advanceBalanceDelta: $delta,
        ));
    }

    /**
     * Las cuatro columnas de moneda que el pago ya congeló. No se vuelven a
     * resolver: el excedente vale lo que valía el dinero el día que salió.
     */
    private function frozenRates(SupplierPayment $payment): DocumentRatesData
    {
        $configuration = $this->configurations->execute($payment->company_id);

        return new DocumentRatesData(
            currency: $payment->currency,
            exchangeRate: (float) $payment->exchange_rate,
            baseCurrency: $payment->base_currency ?? $configuration->base_currency,
            baseExchangeRate: (float) ($payment->base_exchange_rate ?? $payment->exchange_rate),
            rateType: $configuration->rate_type,
            priceDecimals: $configuration->price_decimals,
            amountDecimals: $configuration->amount_decimals,
        );
    }
}
