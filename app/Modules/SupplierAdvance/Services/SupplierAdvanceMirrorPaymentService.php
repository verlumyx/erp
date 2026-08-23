<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierPayment\Commands\CreateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\SearchSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\UpdateStatusSupplierPaymentCommand;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El pago espejo del anticipo: el documento que sí mueve el dinero.
 *
 * Aprobar un anticipo no lo entrega, lo compromete. Lo que se entrega es el
 * pago `PGP` que nace aquí, en borrador y sin aplicaciones a facturas, con los
 * importes copiados del anticipo. Confirmarlo o anularlo es lo que mueve el
 * anticipo (`SupplierAdvancePaymentSyncService`).
 */
class SupplierAdvanceMirrorPaymentService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $payments,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * Crea el pago que entrega el anticipo. Monto, moneda, tasa, proveedor y
     * forma de pago se copian: son propiedad del anticipo y allí se corrigen.
     *
     * @throws ValidationException si el anticipo ya tiene un pago vivo.
     */
    public function create(SupplierAdvance $advance): SupplierPayment
    {
        if ($this->livePayment($advance) instanceof SupplierPayment) {
            throw ValidationException::withMessages([
                'status' => 'El anticipo ya tiene un pago asociado sin anular.',
            ]);
        }

        $id = (string) Str::uuid7();

        $this->payments->create(
            new CreateSupplierPaymentCommand(
                id: $id,
                companyId: $advance->company_id,
                supplierId: $advance->supplier_id,
                paymentDate: $advance->advance_date->toDateString(),
                createdBy: (string) $advance->created_by,
                /** Nace sin reparto: el anticipo no cancela ninguna factura todavía. */
                applications: [],
                originType: SupplierPayment::ORIGIN_ADVANCE,
                originId: $advance->id,
                paymentMethod: $advance->payment_method,
                reference: $advance->reference,
                bankAccount: $advance->bank_account,
                currency: $advance->currency,
                amount: (float) $advance->amount,
                notes: $advance->notes,
            ),
            $this->frozenRates($advance),
        );

        return $this->payments->findOrFail($id, $advance->company_id);
    }

    /**
     * Anula el pago vivo del anticipo, si lo hay. Lo llama el anticipo cuando
     * se anula estando comprometido: el dinero que había quedado apalabrado
     * deja de estarlo.
     */
    public function cancel(SupplierAdvance $advance): void
    {
        $payment = $this->livePayment($advance);

        if (! $payment instanceof SupplierPayment) {
            return;
        }

        $this->payments->updateStatus($payment, new UpdateStatusSupplierPaymentCommand(
            status: 'cancelled',
            cancellationReason: "El anticipo {$advance->code} fue anulado.",
        ));
    }

    /**
     * El pago del anticipo que todavía cuenta. Un anticipo no puede tener más
     * de uno en estado distinto de `cancelled`.
     */
    public function livePayment(SupplierAdvance $advance): ?SupplierPayment
    {
        $result = $this->payments->search(new SearchSupplierPaymentCommand(
            filters: [
                'origin_type' => SupplierPayment::ORIGIN_ADVANCE,
                'origin_id' => $advance->id,
            ],
            limit: 50,
            companyId: $advance->company_id,
        ));

        foreach ($result['data'] as $payment) {
            if ($payment->status !== 'cancelled') {
                return $payment;
            }
        }

        return null;
    }

    /**
     * Las cuatro columnas de moneda que el anticipo ya congeló. No se vuelven a
     * resolver: el pago vale lo que valía el anticipo el día que se capturó.
     */
    private function frozenRates(SupplierAdvance $advance): DocumentRatesData
    {
        $configuration = $this->configurations->execute($advance->company_id);

        return new DocumentRatesData(
            currency: $advance->currency,
            exchangeRate: (float) $advance->exchange_rate,
            baseCurrency: $advance->base_currency ?? $configuration->base_currency,
            baseExchangeRate: (float) ($advance->base_exchange_rate ?? $advance->exchange_rate),
            rateType: $configuration->rate_type,
            priceDecimals: $configuration->price_decimals,
            amountDecimals: $configuration->amount_decimals,
        );
    }
}
