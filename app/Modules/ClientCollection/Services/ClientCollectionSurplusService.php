<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\Client\Commands\ApplyClientBalanceCommand;
use App\Modules\Client\Services\ClientApplyBalanceService;
use App\Modules\ClientAdvance\Commands\CreateClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\SearchClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El dinero que entró y no cancela ninguna factura.
 *
 * Un cobro puede recibir más de lo que reparte —el cliente paga de más, o paga
 * a cuenta sin decir contra qué—. Ese `unapplied_amount` no se queda flotando
 * en la cabecera del cobro: al confirmarlo se convierte en un anticipo `ANC`
 * **ya confirmado**, porque el dinero ya entró con este cobro y no tiene que
 * volver a aprobarse ni generar otro `COB` (`docs/ventas.md` §6.2).
 *
 * Quedan exentos dos casos:
 *
 * - El cobro espejo de un anticipo (`origin_type = 'advance'`): ahí
 *   `unapplied_amount = amount` por definición y el anticipo ya existe.
 * - El cobro que no trae dinero (`payment_method` `advance` o `credit_note`):
 *   lo que no se reparte sigue disponible en el crédito que lo respalda, y
 *   volver a guardarlo como anticipo lo contaría dos veces.
 */
class ClientCollectionSurplusService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $advances,
        private readonly ClientApplyBalanceService $clientBalances,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * Convierte el excedente del cobro en crédito a favor del cliente. Devuelve
     * el anticipo generado, o `null` si no había nada que guardar.
     */
    public function capture(ClientCollection $collection): ?ClientAdvance
    {
        if (! $this->generatesSurplus($collection)) {
            return null;
        }

        $surplus = round((float) $collection->unapplied_amount, 2);

        if ($surplus <= 0) {
            return null;
        }

        return DB::transaction(function () use ($collection, $surplus): ClientAdvance {
            /** Un cobro genera un solo anticipo: confirmarlo dos veces no lo duplica. */
            $existing = $this->generatedAdvance($collection);

            if ($existing instanceof ClientAdvance) {
                return $existing;
            }

            $id = (string) Str::uuid7();

            $this->advances->create(
                new CreateClientAdvanceCommand(
                    id: $id,
                    companyId: (string) $collection->company_id,
                    clientId: $collection->client_id,
                    advanceDate: $collection->collection_date->toDateString(),
                    amount: $surplus,
                    createdBy: (string) $collection->created_by,
                    originCollectionId: $collection->id,
                    paymentMethod: $this->advanceMethod($collection),
                    reference: $collection->reference,
                    bankAccount: $collection->bank_account,
                    currency: $collection->currency,
                    notes: "Excedente del cobro {$collection->code}.",
                ),
                $this->frozenRates($collection),
            );

            $advance = $this->advances->findOrFail($id, $collection->company_id);

            /** Nace recibido: el dinero entró con el cobro que lo generó. */
            $this->advances->updateStatus($advance, new UpdateStatusClientAdvanceCommand('confirmed'));

            $this->moveAdvanceBalance($collection, $surplus);

            return $this->advances->findOrFail($id, $collection->company_id);
        });
    }

    /**
     * Anula el anticipo que el cobro había generado: el dinero que lo respaldaba
     * se va con el cobro.
     *
     * @throws ValidationException si ese anticipo ya se aplicó a facturas.
     */
    public function release(ClientCollection $collection): void
    {
        DB::transaction(function () use ($collection): void {
            $advance = $this->generatedAdvance($collection);

            if (! $advance instanceof ClientAdvance || $advance->status === 'cancelled') {
                return;
            }

            if (round((float) $advance->applied_amount, 2) > 0) {
                throw ValidationException::withMessages([
                    'status' => "El excedente de este cobro quedó en el anticipo {$advance->code}, que ya se aplicó a facturas: revierte esas aplicaciones primero.",
                ]);
            }

            $wasReceived = in_array($advance->status, ['confirmed', 'partial'], true);

            $this->advances->updateStatus($advance, new UpdateStatusClientAdvanceCommand('cancelled'));

            if ($wasReceived) {
                $this->moveAdvanceBalance($collection, -round((float) $advance->amount, 2));
            }
        });
    }

    /**
     * Solo el cobro que trae dinero de verdad y arranca de un cliente o de una
     * factura genera anticipo por lo que le sobra.
     */
    private function generatesSurplus(ClientCollection $collection): bool
    {
        if ($collection->origin_type === ClientCollection::ORIGIN_ADVANCE) {
            return false;
        }

        return ! isset(ClientCollection::CREDIT_METHODS[$collection->payment_method]);
    }

    /** El anticipo que este cobro generó, si llegó a generar alguno. */
    private function generatedAdvance(ClientCollection $collection): ?ClientAdvance
    {
        $result = $this->advances->search(new SearchClientAdvanceCommand(
            filters: ['origin_collection_id' => $collection->id],
            limit: 1,
            companyId: $collection->company_id,
        ));

        return $result['data'][0] ?? null;
    }

    /**
     * La forma de cobro que el anticipo copia. `ANC` no admite las dos que no
     * traen dinero, pero esas ya quedaron exentas más arriba.
     */
    private function advanceMethod(ClientCollection $collection): string
    {
        return in_array($collection->payment_method, ClientAdvance::PAYMENT_METHODS, true)
            ? $collection->payment_method
            : 'other';
    }

    /** El crédito a favor del cliente lo mueve siempre su propio servicio. */
    private function moveAdvanceBalance(ClientCollection $collection, float $delta): void
    {
        $this->clientBalances->execute(new ApplyClientBalanceCommand(
            companyId: $collection->company_id,
            clientId: $collection->client_id,
            advanceBalanceDelta: $delta,
        ));
    }

    /**
     * Las cuatro columnas de moneda que el cobro ya congeló. No se vuelven a
     * resolver: el excedente vale lo que valía el dinero el día que entró.
     */
    private function frozenRates(ClientCollection $collection): DocumentRatesData
    {
        $configuration = $this->configurations->execute($collection->company_id);

        return new DocumentRatesData(
            currency: $collection->currency,
            exchangeRate: (float) $collection->exchange_rate,
            baseCurrency: $collection->base_currency ?? $configuration->base_currency,
            baseExchangeRate: (float) ($collection->base_exchange_rate ?? $collection->exchange_rate),
            rateType: $configuration->rate_type,
            priceDecimals: $configuration->price_decimals,
            amountDecimals: $configuration->amount_decimals,
        );
    }
}
